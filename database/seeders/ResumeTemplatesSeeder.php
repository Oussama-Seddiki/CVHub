<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ResumeTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ResumeTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'الأساسي',
                'thumbnail_path' => '/images/templates/basic.jpg',
                'description' => 'قالب بسيط وأنيق مناسب لجميع المجالات',
                'api_template_id' => 'basic-template-01',
                'is_featured' => true,
                'order' => 1,
                'category_type' => 'سير ذاتية تقليدية'
            ],
            [
                'name' => 'الحديث',
                'thumbnail_path' => '/images/templates/modern.jpg',
                'description' => 'قالب عصري ومميز مع تصميم جذاب',
                'api_template_id' => 'modern-template-01',
                'is_featured' => true,
                'order' => 2,
                'category_type' => 'سير ذاتية حديثة'
            ],
            [
                'name' => 'الاحترافي',
                'thumbnail_path' => '/images/templates/professional.jpg',
                'description' => 'قالب ملائم للمناصب العليا والخبرات المتقدمة',
                'api_template_id' => 'professional-template-01',
                'is_featured' => true,
                'order' => 3,
                'category_type' => 'سير ذاتية حديثة'
            ],
            [
                'name' => 'الإبداعي',
                'thumbnail_path' => '/images/templates/creative.jpg',
                'description' => 'قالب إبداعي مميز للمجالات الإبداعية',
                'api_template_id' => 'creative-template-01',
                'is_featured' => true,
                'order' => 4,
                'category_type' => 'سير ذاتية إبداعية'
            ],
            [
                'name' => 'الأكاديمي',
                'thumbnail_path' => '/images/templates/academic.jpg',
                'description' => 'قالب مناسب للأكاديميين والباحثين',
                'api_template_id' => 'academic-template-01',
                'is_featured' => false,
                'order' => 5,
                'category_type' => 'سير ذاتية أكاديمية'
            ],
            [
                'name' => 'المبسط',
                'thumbnail_path' => '/images/templates/simple.jpg',
                'description' => 'قالب مبسط وسهل القراءة',
                'api_template_id' => 'simple-template-01',
                'is_featured' => false,
                'order' => 6,
                'category_type' => 'سير ذاتية تقليدية'
            ],
            [
                'name' => 'المتقدم',
                'thumbnail_path' => '/images/templates/advanced.jpg',
                'description' => 'قالب متقدم لذوي الخبرة الطويلة',
                'api_template_id' => 'advanced-template-01',
                'is_featured' => false,
                'order' => 7,
                'category_type' => 'سير ذاتية حديثة'
            ]
        ];

        foreach ($templates as $template) {
            // Find the category
            $category = Category::where('name', $template['category_type'])
                ->where('type', 'cv_template')
                ->first();

            if (!$category) {
                continue; // Skip if category not found
            }

            // Create sample structure for the template
            $structure = [
                'sections' => [
                    'personal_info' => [
                        'title' => 'المعلومات الشخصية',
                        'fields' => [
                            'name' => ['label' => 'الاسم الكامل', 'type' => 'text', 'required' => true],
                            'email' => ['label' => 'البريد الإلكتروني', 'type' => 'email', 'required' => true],
                            'phone' => ['label' => 'رقم الهاتف', 'type' => 'tel', 'required' => true],
                            'address' => ['label' => 'العنوان', 'type' => 'text', 'required' => false],
                            'photo' => ['label' => 'الصورة الشخصية', 'type' => 'file', 'required' => false]
                        ]
                    ],
                    'education' => [
                        'title' => 'التعليم',
                        'multiple' => true,
                        'fields' => [
                            'institution' => ['label' => 'المؤسسة التعليمية', 'type' => 'text', 'required' => true],
                            'degree' => ['label' => 'الدرجة العلمية', 'type' => 'text', 'required' => true],
                            'field' => ['label' => 'التخصص', 'type' => 'text', 'required' => true],
                            'start_date' => ['label' => 'تاريخ البدء', 'type' => 'date', 'required' => true],
                            'end_date' => ['label' => 'تاريخ الانتهاء', 'type' => 'date', 'required' => false],
                            'description' => ['label' => 'وصف', 'type' => 'textarea', 'required' => false]
                        ]
                    ],
                    'experience' => [
                        'title' => 'الخبرة المهنية',
                        'multiple' => true,
                        'fields' => [
                            'company' => ['label' => 'الشركة', 'type' => 'text', 'required' => true],
                            'position' => ['label' => 'المنصب', 'type' => 'text', 'required' => true],
                            'start_date' => ['label' => 'تاريخ البدء', 'type' => 'date', 'required' => true],
                            'end_date' => ['label' => 'تاريخ الانتهاء', 'type' => 'date', 'required' => false],
                            'current' => ['label' => 'حالي', 'type' => 'checkbox', 'required' => false],
                            'description' => ['label' => 'وصف المهام', 'type' => 'textarea', 'required' => true]
                        ]
                    ],
                    'skills' => [
                        'title' => 'المهارات',
                        'multiple' => true,
                        'fields' => [
                            'name' => ['label' => 'المهارة', 'type' => 'text', 'required' => true],
                            'level' => ['label' => 'المستوى', 'type' => 'select', 'options' => ['مبتدئ', 'متوسط', 'متقدم', 'خبير'], 'required' => true]
                        ]
                    ],
                    'languages' => [
                        'title' => 'اللغات',
                        'multiple' => true,
                        'fields' => [
                            'name' => ['label' => 'اللغة', 'type' => 'text', 'required' => true],
                            'level' => ['label' => 'المستوى', 'type' => 'select', 'options' => ['مبتدئ', 'متوسط', 'متقدم', 'لغة أم'], 'required' => true]
                        ]
                    ]
                ],
                'layout' => [
                    'header' => ['personal_info'],
                    'left_column' => ['education', 'experience'],
                    'right_column' => ['skills', 'languages']
                ]
            ];

            // Create the template
            ResumeTemplate::create([
                'name' => $template['name'],
                'slug' => Str::slug($template['name']),
                'thumbnail_path' => $template['thumbnail_path'],
                'description' => $template['description'],
                'structure' => $structure,
                'api_template_id' => $template['api_template_id'],
                'is_featured' => $template['is_featured'],
                'is_active' => true,
                'order' => $template['order']
            ]);
        }
    }
}
