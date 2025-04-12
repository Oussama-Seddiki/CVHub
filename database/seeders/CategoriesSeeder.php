<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Document Categories
        $documentCategories = [
            [
                'name' => 'وثائق رسمية',
                'icon' => 'document-text',
                'type' => 'document',
                'description' => 'جميع أنواع الوثائق والمستندات الرسمية',
                'is_featured' => true,
                'order' => 1
            ],
            [
                'name' => 'مذكرات دراسية',
                'icon' => 'academic-cap',
                'type' => 'document',
                'description' => 'مذكرات وملخصات دراسية لمختلف المراحل التعليمية',
                'is_featured' => true,
                'order' => 2
            ],
            [
                'name' => 'نماذج وقوالب',
                'icon' => 'template',
                'type' => 'document',
                'description' => 'نماذج وقوالب جاهزة للاستخدام',
                'is_featured' => true,
                'order' => 3
            ],
            [
                'name' => 'كتب ومراجع',
                'icon' => 'book-open',
                'type' => 'document',
                'description' => 'كتب ومراجع علمية وأدبية',
                'is_featured' => false,
                'order' => 4
            ],
            [
                'name' => 'أدلة وكتيبات',
                'icon' => 'book',
                'type' => 'document',
                'description' => 'أدلة إرشادية وكتيبات توجيهية',
                'is_featured' => false,
                'order' => 5
            ]
        ];

        // CV Template Categories
        $cvCategories = [
            [
                'name' => 'سير ذاتية حديثة',
                'icon' => 'document',
                'type' => 'cv_template',
                'description' => 'قوالب سير ذاتية بتصميم حديث ومميز',
                'is_featured' => true,
                'order' => 1
            ],
            [
                'name' => 'سير ذاتية تقليدية',
                'icon' => 'document-text',
                'type' => 'cv_template',
                'description' => 'قوالب سير ذاتية بتصميم تقليدي',
                'is_featured' => false,
                'order' => 2
            ],
            [
                'name' => 'سير ذاتية إبداعية',
                'icon' => 'sparkles',
                'type' => 'cv_template',
                'description' => 'قوالب سير ذاتية بتصميم إبداعي للمجالات الفنية',
                'is_featured' => true,
                'order' => 3
            ],
            [
                'name' => 'سير ذاتية أكاديمية',
                'icon' => 'academic-cap',
                'type' => 'cv_template',
                'description' => 'قوالب سير ذاتية للأكاديميين والباحثين',
                'is_featured' => false,
                'order' => 4
            ]
        ];

        // File Processing Categories
        $fileTypes = [
            [
                'name' => 'ملفات PDF',
                'icon' => 'document',
                'type' => 'file_type',
                'description' => 'معالجة ملفات PDF (ضغط، دمج، تقسيم)',
                'is_featured' => true,
                'order' => 1
            ],
            [
                'name' => 'ملفات Word',
                'icon' => 'document-text',
                'type' => 'file_type',
                'description' => 'معالجة ملفات Word وتحويلها',
                'is_featured' => true,
                'order' => 2
            ],
            [
                'name' => 'صور',
                'icon' => 'photograph',
                'type' => 'file_type',
                'description' => 'معالجة الصور وتحويلها',
                'is_featured' => true,
                'order' => 3
            ],
            [
                'name' => 'ملفات أخرى',
                'icon' => 'document-duplicate',
                'type' => 'file_type',
                'description' => 'معالجة أنواع أخرى من الملفات',
                'is_featured' => false,
                'order' => 4
            ]
        ];

        // Save all categories
        foreach (array_merge($documentCategories, $cvCategories, $fileTypes) as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'icon' => $category['icon'],
                'type' => $category['type'],
                'description' => $category['description'],
                'is_featured' => $category['is_featured'],
                'order' => $category['order'],
                'is_active' => true
            ]);
        }
    }
}
