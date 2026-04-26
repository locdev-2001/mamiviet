<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug->de' => 'ueber-uns'],
            [
                'slug' => ['de' => 'ueber-uns', 'en' => 'about'],
                'status' => 'published',
                'seo' => [
                    'de' => [
                        'title' => 'Über uns — Mamiviet Restaurant Leipzig',
                        'description' => 'Erfahren Sie mehr über Mamiviet, unser vietnamesisches Restaurant in der Merseburger Straße 107 in Leipzig.',
                        'keywords' => 'Mamiviet, vietnamesisches Restaurant Leipzig, Über uns, Geschichte',
                    ],
                    'en' => [
                        'title' => 'About Us — Mamiviet Restaurant Leipzig',
                        'description' => 'Learn more about Mamiviet, our Vietnamese restaurant at Merseburger Straße 107 in Leipzig.',
                        'keywords' => 'Mamiviet, Vietnamese restaurant Leipzig, About us, Our story',
                    ],
                ],
                'content' => [
                    'de' => [
                        'title' => 'Über uns',
                        'body' => '<p>Willkommen bei <strong>Mamiviet</strong> – Ihrem vietnamesischen Restaurant in Leipzig.</p>

<p>Unser Restaurant befindet sich in der <strong>Merseburger Straße 107, 04177 Leipzig</strong> und öffnet seine Türen täglich von 11:00 bis 14:00 Uhr und von 17:00 bis 22:00 Uhr.</p>

<h2>Unsere Geschichte</h2>

<p>Mamiviet steht für authentische vietnamesische Küche, die mit Leidenschaft und frischen Zutaten zubereitet wird. Unser Name vereint zwei Welten: „Mami" – für die Herzlichkeit der vietnamesischen Gastfreundschaft – und „Viet" – für unsere kulturellen Wurzeln in Vietnam.</p>

<h2>Unsere Philosophie</h2>

<p>Wir glauben, dass gutes Essen Menschen verbindet. Jedes Gericht auf unserer Karte erzählt eine Geschichte – von frischen Zutaten, traditionellen Rezepten und dem tiefen Respekt für die vietnamesische Kochkunst.</p>

<p>Unsere Küche kombiniert klassische vietnamesische Aromen mit modernen Einflüssen, um Ihnen ein unvergessliches Geschmackserlebnis zu bieten.</p>

<h2>Besuchen Sie uns</h2>

<p>Wir freuen uns auf Ihren Besuch! Egal ob zum Mittagessen, Abendessen oder einem besonderen Anlass – bei Mamiviet sind Sie herzlich willkommen.</p>',
                    ],
                    'en' => [
                        'title' => 'About Us',
                        'body' => '<p>Welcome to <strong>Mamiviet</strong> – your Vietnamese restaurant in Leipzig.</p>

<p>Our restaurant is located at <strong>Merseburger Straße 107, 04177 Leipzig</strong> and opens its doors daily from 11:00 AM to 2:00 PM and from 5:00 PM to 10:00 PM.</p>

<h2>Our Story</h2>

<p>Mamiviet stands for authentic Vietnamese cuisine prepared with passion and fresh ingredients. Our name unites two worlds: "Mami" – for the warmth of Vietnamese hospitality – and "Viet" – for our cultural roots in Vietnam.</p>

<h2>Our Philosophy</h2>

<p>We believe that good food brings people together. Every dish on our menu tells a story – of fresh ingredients, traditional recipes, and deep respect for the Vietnamese culinary arts.</p>

<p>Our kitchen combines classic Vietnamese flavors with modern influences to offer you an unforgettable taste experience.</p>

<h2>Visit Us</h2>

<p>We look forward to welcoming you! Whether for lunch, dinner, or a special occasion – you are warmly welcome at Mamiviet.</p>',
                    ],
                ],
            ]
        );
    }
}
