<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'status' => 'published',
                'published_at' => now()->subDays(7),
                'title' => [
                    'de' => 'Pho in Leipzig — Unser Geheimrezept aus Hanoi',
                    'en' => 'Pho in Leipzig — Our Secret Recipe from Hanoi',
                ],
                'slug' => [
                    'de' => 'pho-leipzig-geheimrezept-hanoi',
                    'en' => 'pho-leipzig-secret-recipe-hanoi',
                ],
                'excerpt' => [
                    'de' => 'Entdecken Sie die Geschichte hinter unserem authentischen Pho — 12 Stunden Rinderbrühe, frische Kräuter und Gewürze direkt aus Vietnam.',
                    'en' => 'Discover the story behind our authentic Pho — 12-hour beef broth, fresh herbs and spices straight from Vietnam.',
                ],
                'content' => [
                    'de' => '<h2>Die Kunst des langsamen Kochens</h2><p>Pho ist mehr als eine Suppe — es ist die Seele der vietnamesischen Küche. Unsere Brühe köchelt <strong>12 Stunden lang</strong> mit Rinderknochen, Sternanis, Zimt und Ingwer.</p><h3>Die wichtigsten Zutaten</h3><ul><li>Rinderknochen aus regionaler Bio-Haltung</li><li>Frische Kräuter: Thai-Basilikum, Koriander, Minze</li><li>Gewürze direkt aus Vietnam importiert</li></ul><blockquote>In jedem Löffel steckt die Erinnerung an Hanoi.</blockquote><p>Besuchen Sie uns in Leipzig und erleben Sie den Unterschied.</p>',
                    'en' => '<h2>The art of slow cooking</h2><p>Pho is more than a soup — it is the soul of Vietnamese cuisine. Our broth simmers for <strong>12 hours</strong> with beef bones, star anise, cinnamon, and ginger.</p><h3>The essential ingredients</h3><ul><li>Organic beef bones from regional farms</li><li>Fresh herbs: Thai basil, cilantro, mint</li><li>Spices imported directly from Vietnam</li></ul><blockquote>Every spoonful carries the memory of Hanoi.</blockquote><p>Visit us in Leipzig and taste the difference.</p>',
                ],
                'seo_title' => [
                    'de' => 'Authentisches Pho in Leipzig — Mamiviet',
                    'en' => 'Authentic Pho in Leipzig — Mamiviet',
                ],
                'seo_description' => [
                    'de' => 'Unser Pho-Rezept aus Hanoi: 12 Stunden Brühe, frische Kräuter, echte vietnamesische Tradition in Leipzig.',
                    'en' => 'Our Pho recipe from Hanoi: 12-hour broth, fresh herbs, real Vietnamese tradition in Leipzig.',
                ],
                'seo_keywords' => [
                    'de' => 'pho leipzig, vietnamesische suppe, authentische küche, hanoi rezept',
                    'en' => 'pho leipzig, vietnamese soup, authentic cuisine, hanoi recipe',
                ],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'title' => [
                    'de' => 'Vietnamesisches Tet-Fest — Neujahr bei Mamiviet',
                    'en' => 'Vietnamese Tet Festival — New Year at Mamiviet',
                ],
                'slug' => [
                    'de' => 'vietnamesisches-tet-fest-neujahr',
                    'en' => 'vietnamese-tet-festival-new-year',
                ],
                'excerpt' => [
                    'de' => 'Feiern Sie Tet mit uns — traditionelle Gerichte, Banh Chung, Lotusblütentee und die Geschichten hinter jedem Gericht.',
                    'en' => 'Celebrate Tet with us — traditional dishes, Banh Chung, lotus tea, and the stories behind each dish.',
                ],
                'content' => [
                    'de' => '<h2>Tet — das wichtigste Fest Vietnams</h2><p>Tet Nguyen Dan markiert den Beginn des Mondjahres. In Mamiviet feiern wir diese Tradition mit einem speziellen Menü.</p><h3>Unser Tet-Menü</h3><ul><li><strong>Banh Chung</strong> — quadratischer Reiskuchen mit Schweinefleisch und grünen Bohnen</li><li><strong>Xoi Gac</strong> — roter klebriger Reis mit Gac-Frucht</li><li><strong>Thit Kho Tau</strong> — geschmortes Schweinefleisch mit Ei</li></ul><p>Reservieren Sie jetzt Ihren Tisch für das Tet-Fest.</p>',
                    'en' => '<h2>Tet — Vietnam\'s most important festival</h2><p>Tet Nguyen Dan marks the start of the lunar new year. At Mamiviet, we celebrate this tradition with a special menu.</p><h3>Our Tet menu</h3><ul><li><strong>Banh Chung</strong> — square sticky rice cake with pork and mung beans</li><li><strong>Xoi Gac</strong> — red sticky rice with gac fruit</li><li><strong>Thit Kho Tau</strong> — braised pork belly with eggs</li></ul><p>Reserve your table for the Tet celebration now.</p>',
                ],
                'seo_title' => [
                    'de' => 'Tet-Fest in Leipzig — Mamiviet Restaurant',
                    'en' => 'Tet Festival in Leipzig — Mamiviet Restaurant',
                ],
                'seo_description' => [
                    'de' => 'Vietnamesisches Neujahr in Leipzig. Traditionelles Tet-Menü, Banh Chung, Lotusblütentee. Jetzt reservieren.',
                    'en' => 'Vietnamese New Year in Leipzig. Traditional Tet menu, Banh Chung, lotus tea. Reserve now.',
                ],
                'seo_keywords' => [
                    'de' => 'tet fest, vietnamesisches neujahr, leipzig, banh chung',
                    'en' => 'tet festival, vietnamese new year, leipzig, banh chung',
                ],
            ],
            [
                'status' => 'draft',
                'published_at' => null,
                'title' => [
                    'de' => 'Banh Mi — das beste Baguette Saigons',
                    'en' => 'Banh Mi — Saigon\'s best baguette',
                ],
                'slug' => [
                    'de' => 'banh-mi-bestes-baguette-saigon',
                    'en' => 'banh-mi-saigon-best-baguette',
                ],
                'excerpt' => [
                    'de' => 'Ein Entwurf über die Geschichte und die Varianten von Banh Mi in unserem Restaurant.',
                    'en' => 'A draft about the history and varieties of Banh Mi at our restaurant.',
                ],
                'content' => [
                    'de' => '<p><em>Entwurf in Arbeit…</em></p><p>Banh Mi ist das Ergebnis einer kulinarischen Fusion aus französischer und vietnamesischer Tradition.</p>',
                    'en' => '<p><em>Draft in progress…</em></p><p>Banh Mi is the result of a culinary fusion between French and Vietnamese traditions.</p>',
                ],
            ],
        ];

        foreach ($posts as $data) {
            Post::query()->updateOrCreate(
                ['slug->de' => $data['slug']['de']],
                $data,
            );
        }
    }
}
