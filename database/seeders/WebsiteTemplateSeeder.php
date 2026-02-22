<?php

namespace Database\Seeders;

use App\Modules\Website\Models\WebsiteTemplate;
use Illuminate\Database\Seeder;

class WebsiteTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Aurora Studio',
                'slug' => 'aurora-studio',
                'description' => 'Bold creative studio layout with cinematic hero and showcase sections.',
                'theme' => [
                    'colors' => [
                        'primary' => '#0F172A',
                        'secondary' => '#F97316',
                        'background' => '#F8FAFC',
                        'text' => '#0F172A',
                    ],
                    'font' => [
                        'heading' => 'Playfair Display',
                        'body' => 'Inter',
                    ],
                ],
            ],
            [
                'name' => 'Atlas Consulting',
                'slug' => 'atlas-consulting',
                'description' => 'Professional consulting layout with credibility blocks and service highlights.',
                'theme' => [
                    'colors' => [
                        'primary' => '#1E3A8A',
                        'secondary' => '#FBBF24',
                        'background' => '#F9FAFB',
                        'text' => '#111827',
                    ],
                    'font' => [
                        'heading' => 'Merriweather',
                        'body' => 'Source Sans Pro',
                    ],
                ],
            ],
            [
                'name' => 'Bloom Wellness',
                'slug' => 'bloom-wellness',
                'description' => 'Soft wellness design with calm palette and wellness programs.',
                'theme' => [
                    'colors' => [
                        'primary' => '#047857',
                        'secondary' => '#FDE68A',
                        'background' => '#F0FDF4',
                        'text' => '#064E3B',
                    ],
                    'font' => [
                        'heading' => 'Lora',
                        'body' => 'Inter',
                    ],
                ],
            ],
            [
                'name' => 'Copper Agency',
                'slug' => 'copper-agency',
                'description' => 'Modern agency theme focused on portfolios and client results.',
                'theme' => [
                    'colors' => [
                        'primary' => '#7C2D12',
                        'secondary' => '#FDBA74',
                        'background' => '#FFF7ED',
                        'text' => '#431407',
                    ],
                    'font' => [
                        'heading' => 'Bebas Neue',
                        'body' => 'Inter',
                    ],
                ],
            ],
            [
                'name' => 'Drift Ventures',
                'slug' => 'drift-ventures',
                'description' => 'Startup landing layout with product story and investor confidence.',
                'theme' => [
                    'colors' => [
                        'primary' => '#0F766E',
                        'secondary' => '#22D3EE',
                        'background' => '#ECFEFF',
                        'text' => '#0F172A',
                    ],
                    'font' => [
                        'heading' => 'Space Grotesk',
                        'body' => 'Inter',
                    ],
                ],
            ],
            [
                'name' => 'Echo Education',
                'slug' => 'echo-education',
                'description' => 'Education-focused theme with courses, instructors, and testimonials.',
                'theme' => [
                    'colors' => [
                        'primary' => '#1D4ED8',
                        'secondary' => '#F97316',
                        'background' => '#EFF6FF',
                        'text' => '#1E3A8A',
                    ],
                    'font' => [
                        'heading' => 'DM Serif Display',
                        'body' => 'Inter',
                    ],
                ],
            ],
            [
                'name' => 'Forge Studio',
                'slug' => 'forge-studio',
                'description' => 'Industrial-inspired theme with strong typography and process blocks.',
                'theme' => [
                    'colors' => [
                        'primary' => '#111827',
                        'secondary' => '#10B981',
                        'background' => '#F9FAFB',
                        'text' => '#111827',
                    ],
                    'font' => [
                        'heading' => 'Oswald',
                        'body' => 'Inter',
                    ],
                ],
            ],
            [
                'name' => 'Horizon Realty',
                'slug' => 'horizon-realty',
                'description' => 'Real estate theme with property highlights and contact CTA.',
                'theme' => [
                    'colors' => [
                        'primary' => '#0F172A',
                        'secondary' => '#38BDF8',
                        'background' => '#F1F5F9',
                        'text' => '#0F172A',
                    ],
                    'font' => [
                        'heading' => 'Cormorant Garamond',
                        'body' => 'Inter',
                    ],
                ],
            ],
            [
                'name' => 'Lumen Labs',
                'slug' => 'lumen-labs',
                'description' => 'Tech lab theme with product highlights and team sections.',
                'theme' => [
                    'colors' => [
                        'primary' => '#312E81',
                        'secondary' => '#A5B4FC',
                        'background' => '#EEF2FF',
                        'text' => '#1E1B4B',
                    ],
                    'font' => [
                        'heading' => 'Sora',
                        'body' => 'Inter',
                    ],
                ],
            ],
            [
                'name' => 'Northwind Brand',
                'slug' => 'northwind-brand',
                'description' => 'Brand showcase layout with story-first narrative and gallery.',
                'theme' => [
                    'colors' => [
                        'primary' => '#4C0519',
                        'secondary' => '#FDA4AF',
                        'background' => '#FFF1F2',
                        'text' => '#4C0519',
                    ],
                    'font' => [
                        'heading' => 'Playfair Display',
                        'body' => 'Inter',
                    ],
                ],
            ],
        ];

        foreach ($templates as $template) {
            $template['config'] = $this->buildTemplateConfig($template['name'], $template['theme']);
            unset($template['theme']);
            WebsiteTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                array_merge($template, [
                    'is_active' => true,
                    'is_default' => false,
                ])
            );
        }
    }

    private function buildTemplateConfig(string $brandName, array $theme): array
    {
        return [
            'theme' => $theme,
            'pages' => [
                [
                    'title' => 'Home',
                    'slug' => 'home',
                    'page_type' => 'home',
                    'status' => 'published',
                    'content' => [
                        'blocks' => [
                            [
                                'type' => 'hero',
                                'content' => [
                                    'title' => $brandName,
                                    'subtitle' => 'Crafting memorable digital experiences.',
                                    'ctaText' => 'Get in Touch',
                                ],
                            ],
                            [
                                'type' => 'features',
                                'content' => [
                                    'title' => 'What We Do',
                                    'items' => [
                                        ['title' => 'Strategy', 'description' => 'Clear plans with measurable outcomes.'],
                                        ['title' => 'Design', 'description' => 'Bold visuals that tell your story.'],
                                        ['title' => 'Delivery', 'description' => 'Fast, reliable execution.'],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'testimonials',
                                'content' => [
                                    'title' => 'Client Stories',
                                ],
                            ],
                            [
                                'type' => 'cta',
                                'content' => [
                                    'title' => 'Ready to build your site?',
                                    'subtitle' => 'Launch a high-impact website in days.',
                                    'ctaText' => 'Start Now',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'About Us',
                    'slug' => 'about',
                    'page_type' => 'about',
                    'status' => 'published',
                    'content' => [
                        'blocks' => [
                            [
                                'type' => 'section',
                                'content' => [
                                    'title' => 'About ' . $brandName,
                                    'subtitle' => 'We are a team of builders, designers, and storytellers.',
                                ],
                            ],
                            [
                                'type' => 'team',
                                'content' => [
                                    'title' => 'Our Team',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Contact',
                    'slug' => 'contact',
                    'page_type' => 'contact',
                    'status' => 'published',
                    'content' => [
                        'blocks' => [
                            [
                                'type' => 'contact',
                                'content' => [
                                    'title' => 'Let\'s Connect',
                                    'subtitle' => 'Tell us about your project.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

