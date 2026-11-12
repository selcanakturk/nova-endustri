<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = [];

            foreach ($this->demoProducts() as $index => $demoProduct) {
                $categoryName = $demoProduct['category'];

                if (! isset($categories[$categoryName])) {
                    $categories[$categoryName] = Category::firstOrCreate(
                        ['slug' => Str::slug($categoryName, '-', 'tr')],
                        ['name' => $categoryName, 'sort_order' => count($categories), 'is_active' => true],
                    );
                }

                Product::firstOrCreate(
                    ['slug' => Str::slug($demoProduct['name'], '-', 'tr')],
                    [
                        'category_id' => $categories[$categoryName]->id,
                        'name' => $demoProduct['name'],
                        'short_description' => $demoProduct['description'],
                        'description' => $demoProduct['description'],
                        'image' => 'images/'.$demoProduct['image'],
                        'is_featured' => $index < 3,
                        'is_active' => true,
                        'sort_order' => $index,
                    ],
                );
            }
        });
    }

    /** @return list<array{name: string, category: string, image: string, description: string}> */
    private function demoProducts(): array
    {
        return [
            ['name' => 'Hassas işlenmiş parçalar', 'category' => 'Talaşlı imalat', 'image' => 'precision.svg', 'description' => 'Makine ve ekipmanlarda kullanılmak üzere teknik resme göre şekillendirilen metal bileşenler.'],
            ['name' => 'Güç aktarım bileşenleri', 'category' => 'Mekanik sistemler', 'image' => 'transmission.svg', 'description' => 'Hareketin kontrollü aktarımı için mil, flanş ve bağlantı elemanlarından oluşan ürün grubu.'],
            ['name' => 'Özel üretim gövdeler', 'category' => 'Metal konstrüksiyon', 'image' => 'housing.svg', 'description' => 'Montaj ihtiyaçlarına göre tasarlanabilen, yapısal bütünlüğü ön planda tutan metal gövdeler.'],
            ['name' => 'Bağlantı ve montaj elemanları', 'category' => 'Talaşlı imalat', 'image' => 'precision.svg', 'description' => 'Mekanik montajların bir araya getirilmesine yönelik ara parçalar ve bağlantı çözümleri.'],
            ['name' => 'Yataklama bileşenleri', 'category' => 'Mekanik sistemler', 'image' => 'transmission.svg', 'description' => 'Dönen parçaların konumlandırılmasına yönelik örnek mekanik destek bileşenleri.'],
            ['name' => 'Makine taşıyıcı şaseleri', 'category' => 'Metal konstrüksiyon', 'image' => 'housing.svg', 'description' => 'Ekipman yerleşimi ve montaj düzeni için projeye göre ele alınan taşıyıcı yapılar.'],

        ];
    }
}
