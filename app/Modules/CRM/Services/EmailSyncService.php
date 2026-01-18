<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\EmailAccount;
use App\Modules\CRM\Models\EmailMessage;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\Contact;
use App\Modules\CRM\Models\Deal;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Client;

class EmailSyncService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Sync emails for a specific email account.
     */
    public function syncAccount(EmailAccount $emailAccount): int
    {
        if (!$emailAccount->is_active || $emailAccount->type !== 'imap') {
            return 0;
        }

        try {
            $client = $this->createImapClient($emailAccount);
            $folder = $client->getFolder('INBOX');
            
            $messages = $folder->messages()->unseen()->get();
            $syncedCount = 0;

            foreach ($messages as $imapMessage) {
                if ($this->syncMessage($emailAccount, $imapMessage)) {
                    $syncedCount++;
                }
            }

            return $syncedCount;
        } catch (\Exception $e) {
            Log::error('Email sync failed for account ' . $emailAccount->id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync all active email accounts.
     */
    public function syncAllAccounts(): int
    {
        $accounts = EmailAccount::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('is_active', true)
            ->where('auto_sync', true)
            ->where('type', 'imap')
            ->get();

        $totalSynced = 0;

        foreach ($accounts as $account) {
            try {
                $totalSynced += $this->syncAccount($account);
            } catch (\Exception $e) {
                Log::error('Failed to sync account ' . $account->id . ': ' . $e->getMessage());
            }
        }

        return $totalSynced;
    }

    /**
     * Create IMAP client from email account.
     */
    protected function createImapClient(EmailAccount $emailAccount): Client
    {
        $credentials = $emailAccount->credentials;
        
        $config = [
            'host' => $credentials['host'] ?? 'imap.gmail.com',
            'port' => $credentials['port'] ?? 993,
            'encryption' => $credentials['encryption'] ?? 'ssl',
            'validate_cert' => $credentials['validate_cert'] ?? true,
            'username' => $emailAccount->email,
            'password' => $credentials['password'],
        ];

        return new Client($config);
    }

    /**
     * Sync a single IMAP message to database.
     */
    protected function syncMessage(EmailAccount $emailAccount, $imapMessage): bool
    {
        try {
            $messageId = $imapMessage->getMessageId();
            
            // Check if message already exists
            if (EmailMessage::where('message_id', $messageId)->exists()) {
                return false;
            }

            $from = $imapMessage->getFrom();
            $to = $imapMessage->getTo();
            $cc = $imapMessage->getCc();
            $bcc = $imapMessage->getBcc();
            $subject = $imapMessage->getSubject();
            $body = $imapMessage->getTextBody() ?: $imapMessage->getHTMLBody();
            $date = $imapMessage->getDate();

            // Extract email addresses
            $fromEmails = $this->extractEmailAddresses($from);
            $toEmails = $this->extractEmailAddresses($to);
            $ccEmails = $cc ? $this->extractEmailAddresses($cc) : [];
            $bccEmails = $bcc ? $this->extractEmailAddresses($bcc) : [];

            // Find related entity (Lead, Contact, or Deal)
            $related = $this->findRelatedEntity($fromEmails, $toEmails);

            $emailMessage = EmailMessage::create([
                'tenant_id' => $this->tenantContext->getTenantId(),
                'email_account_id' => $emailAccount->id,
                'message_id' => $messageId,
                'subject' => $subject,
                'body' => $body,
                'from_email' => $fromEmails[0] ?? $emailAccount->email,
                'from_name' => $this->extractName($from),
                'to' => $toEmails,
                'cc' => !empty($ccEmails) ? $ccEmails : null,
                'bcc' => !empty($bccEmails) ? $bccEmails : null,
                'direction' => 'incoming',
                'related_type' => $related ? get_class($related) : null,
                'related_id' => $related ? $related->id : null,
                'received_at' => $date,
                'is_read' => false,
                'attachments' => $this->extractAttachments($imapMessage),
            ]);

            // Mark as read in IMAP
            $imapMessage->setFlag('Seen');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract email addresses from IMAP address collection.
     */
    protected function extractEmailAddresses($addresses): array
    {
        if (!$addresses) {
            return [];
        }

        $emails = [];
        foreach ($addresses as $address) {
            if (is_object($address) && method_exists($address, 'mail')) {
                $emails[] = $address->mail;
            } elseif (is_string($address)) {
                $emails[] = $address;
            } elseif (is_array($address) && isset($address['mail'])) {
                $emails[] = $address['mail'];
            }
        }

        return array_filter($emails);
    }

    /**
     * Extract name from IMAP address collection.
     */
    protected function extractName($addresses): ?string
    {
        if (!$addresses) {
            return null;
        }

        $first = is_array($addresses) ? ($addresses[0] ?? null) : $addresses;
        
        if (is_object($first) && method_exists($first, 'personal')) {
            return $first->personal;
        } elseif (is_array($first) && isset($first['personal'])) {
            return $first['personal'];
        }

        return null;
    }

    /**
     * Find related entity (Lead, Contact, or Deal) by email address.
     */
    protected function findRelatedEntity(array $fromEmails, array $toEmails): ?object
    {
        $emails = array_unique(array_merge($fromEmails, $toEmails));
        $emails = array_filter($emails);

        // Try to find Contact first
        foreach ($emails as $email) {
            $contact = Contact::where('tenant_id', $this->tenantContext->getTenantId())
                ->where('email', $email)
                ->first();
            
            if ($contact) {
                return $contact;
            }
        }

        // Try to find Lead
        foreach ($emails as $email) {
            $lead = Lead::where('tenant_id', $this->tenantContext->getTenantId())
                ->where('email', $email)
                ->first();
            
            if ($lead) {
                return $lead;
            }
        }

        // Try to find Deal through Contact
        foreach ($emails as $email) {
            $contact = Contact::where('tenant_id', $this->tenantContext->getTenantId())
                ->where('email', $email)
                ->first();
            
            if ($contact) {
                $deal = Deal::where('tenant_id', $this->tenantContext->getTenantId())
                    ->where('contact_id', $contact->id)
                    ->first();
                
                if ($deal) {
                    return $deal;
                }
            }
        }

        return null;
    }

    /**
     * Extract attachments from IMAP message.
     */
    protected function extractAttachments($imapMessage): array
    {
        $attachments = [];
        
        try {
            $attachmentsCollection = $imapMessage->getAttachments();
            
            foreach ($attachmentsCollection as $attachment) {
                $attachments[] = [
                    'name' => $attachment->getName(),
                    'size' => $attachment->getSize(),
                    'content_type' => $attachment->getContentType(),
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract attachments: ' . $e->getMessage());
        }

        return $attachments;
    }
}
