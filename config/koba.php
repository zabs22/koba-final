<?php

/*
|--------------------------------------------------------------------------
| KOBA public website — media & contact registry
|--------------------------------------------------------------------------
|
| Images live in public/images/koba as "<key>-<width>.webp". Each entry is
| [intrinsic width, intrinsic height, available widths, alt text]. The
| intrinsic size gives the browser the aspect ratio up front (no layout
| shift); renditions are generated from the original photography.
|
*/

return [

    'images' => [
        // Homepage hero: cut-out subject (transparent) over the koba leaf band.
        'hero-croissant' => [712, 737, [480, 712], 'A KOBA croissant wrapped in a green koba leaf, held in hand'],
        'hero-leaves' => [1515, 392, [960, 1515], ''],
        'hero-leaves-alpha' => [1515, 392, [960, 1515], ''],

        'croissant' => [3799, 5762, [800, 1400, 2200], 'A glossy, freshly baked KOBA croissant on a dark wooden board'],
        'almond-croissant' => [4160, 6240, [480, 960, 1600], 'Almond croissant dusted with icing sugar'],
        'bomboloni' => [3860, 5919, [480, 960, 1600], 'Sugar-dusted doughnut on a dark serving board'],
        'pecan-tart' => [1024, 1536, [480, 960, 1024], 'A slice of glazed pecan tart'],
        'mocha-slice' => [3959, 5697, [480, 960, 1600], 'Layered mocha slice topped with a chocolate shard'],
        'eclair' => [3960, 5940, [480, 960, 1600], 'Glazed éclair finished with coffee beans'],
        'opera-slice' => [3831, 5802, [480, 960, 1600], 'Opera slice with fine layers and a chocolate plaque'],
        'glazed-dome' => [1360, 2048, [480, 960, 1360], 'Mirror-glazed dome entremet on a crumble base'],

        'cake-opera-chocolate' => [3841, 6074, [480, 960, 1600], 'Chocolate opera cake with a piped treble clef'],
        'cake-red-velvet' => [1360, 2048, [480, 960, 1360], 'Red-topped layered cake, sliced into portions'],
        'cake-cheesecake' => [1360, 2048, [480, 960, 1360], 'Cheesecake with a glossy berry top, sliced'],
        'cake-opera-caramel' => [3738, 5963, [480, 960, 1600], 'Square caramel opera cake with piped detailing'],
        'cake-basque' => [3725, 6187, [480, 960, 1600], 'Caramelised baked cheesecake'],
        'cake-vanilla' => [4091, 6189, [480, 960, 1600], 'Layered vanilla cake with a white glaze and chocolate seals'],
        'cake-meringue' => [3980, 6036, [480, 960, 1600], 'Layered cake crowned with piped meringue'],
        'cake-chocolate-mousse' => [1360, 2048, [480, 960, 1360], 'Chocolate mousse cake with cream pearls'],
        'cake-trio' => [1360, 2048, [480, 960, 1360], 'Square chocolate cake with glossy chocolate lines'],
        'cake-chocolate-fudge' => [1360, 2048, [480, 960, 1360], 'Round chocolate cake with a mirror glaze'],
        'cake-opera-cocoa' => [3960, 6074, [480, 960, 1600], 'Layered chocolate cake dusted with cocoa'],

        'drink-layered' => [1600, 2000, [480, 960, 1600], 'Layered fresh juice in a glass mug'],
        'drink-pineapple-mojito' => [1600, 2000, [480, 960, 1600], 'Pineapple and mint cooler'],
        'drink-green-mint' => [1600, 2000, [480, 960, 1600], 'Green mint drink with a sprig of fresh mint'],
        'drink-affogato' => [1600, 2000, [480, 960, 1600], 'Affogato in a glass cup'],
        'drink-red-juice' => [1600, 2000, [480, 960, 1600], 'Deep red fresh juice'],
        'drink-tea-pour' => [1600, 2000, [480, 960, 1600], 'Milk tea being poured from a white teapot into a glass'],
        'drink-macchiato' => [1600, 2000, [480, 960, 1600], 'Layered espresso in a glass cup on a warm wooden table'],
        'drink-iced-americano' => [1600, 2000, [480, 960, 1600], 'Iced coffee on a wooden table beside a velvet cushion'],
        'drink-iced-latte' => [1600, 2000, [480, 960, 1600], 'Iced latte in front of green plants'],
        'drink-cappuccino' => [1600, 2000, [480, 960, 1600], 'Milk coffee with latte art'],
        'drink-mocha' => [1466, 2000, [480, 960, 1466], 'Mocha with chocolate latte art'],
        'drink-iced-latte-terrace' => [1600, 2000, [480, 960, 1600], 'Iced latte on a terrace table surrounded by greenery'],
        'drink-iced-mocha' => [1467, 2000, [480, 960, 1467], 'Iced mocha on a red wooden table among plants'],
        'drink-flat-white' => [1600, 2000, [480, 960, 1600], 'Flat white with a heart in the foam'],
        'drink-layered-terrace' => [1600, 2000, [480, 960, 1600], 'Layered juice on a terrace table'],
        'drink-strawberry-mojito' => [1600, 2000, [480, 960, 1600], 'Strawberry and mint cooler'],
        'drink-mojito' => [1600, 2000, [480, 960, 1600], 'Lime and mint cooler with crushed ice'],
        'drink-chocolate-pour' => [1600, 2000, [480, 960, 1600], 'Hot milk poured over a chocolate sphere'],

        'craft-lamination' => [1520, 1325, [480, 960, 1520], 'Close-up of the laminated layers of a croissant'],
        'craft-glaze' => [626, 532, [480, 626], 'Close-up of a mirror glaze'],
        'craft-crumb' => [2107, 1567, [480, 960, 1600], 'Close-up of the fine layers of an opera slice'],
        'craft-dusting' => [1853, 1361, [480, 960, 1600], 'Close-up of sugar dusting on a doughnut'],
        'craft-finish' => [2218, 1782, [480, 960, 1600], 'Close-up of a glazed éclair'],
        'craft-almond' => [2080, 1685, [480, 960, 1600], 'Close-up of toasted almonds on a croissant'],
    ],

    /*
    | Dish photographs, keyed by Str::slug() of the dish name. Dishes without
    | a real photograph are simply left out — never substitute another dish.
    */
    'dish_images' => [
        'trio' => 'cake-trio',
        'chocolate-fudge' => 'cake-chocolate-fudge',
        'opera-caramel' => 'cake-opera-caramel',
        'vanilla-eclair' => 'cake-vanilla',
        'cheesecake' => 'cake-cheesecake',
        'koba-croissant' => 'croissant',
        'almond-croissant' => 'almond-croissant',
        'pecan-tart' => 'pecan-tart',
        'eclair' => 'eclair',
        'espresso-milk-coffee' => 'drink-macchiato',
        'tea' => 'drink-tea-pour',
        'iced-coffee' => 'drink-iced-latte',
        'fresh-cold-drinks' => 'drink-mojito',
    ],

    /*
    |--------------------------------------------------------------------------
    | Google reviews ("Heard at KOBA")
    |--------------------------------------------------------------------------
    |
    | There is no Google Places / Business Profile integration yet, so this is
    | explicitly supplied data — nothing here is invented. Ratings, counts and
    | review text were transcribed from the public Google Maps listings and
    | are a snapshot: refresh them (or replace this block with a server-side
    | Places API fetch, key kept in .env, cached) to keep them current.
    |
    | Per listing: name (exact Google listing title), short (card label),
    | image (decorative photo key from 'images' above, optional),
    | rating + count (the listing's overall Google rating), maps_url (the
    | listing's own Google Maps link — paste it to override the search link
    | built from maps_query), and reviews[]: text (verbatim) plus optional
    | author, rating (that review's own stars) and date, shown only when set.
    |
    */
    'google_reviews' => [
        'listings' => [
            [
                'key' => 'atlas',
                'name' => 'KOBA Patisserie & Bakery | Atlas Branch',
                'short' => 'Atlas Branch',
                'rating' => 4.5,
                'count' => 604,
                'maps_url' => null,
                'maps_query' => 'KOBA Patisserie & Bakery Atlas Branch Addis Ababa',
                'reviews' => [
                    ['text' => 'It\'s a cozy place with variety of pastries & very nice customer service.', 'author' => null, 'rating' => null, 'date' => null],
                ],
            ],
            [
                'key' => '4-kilo',
                'name' => 'KOBA Patisserie & Bakery - 4 Kilo Branch',
                'short' => '4 Kilo Branch',
                'rating' => 4.3,
                'count' => 136,
                'maps_url' => null,
                'maps_query' => 'KOBA Patisserie & Bakery 4 Kilo Branch King George VI St Addis Ababa',
                'reviews' => [
                    ['text' => 'Staff is highly professional and food and pastries are wonderful!', 'author' => null, 'rating' => null, 'date' => null],
                ],
            ],
            [
                'key' => 'kebena',
                'name' => 'KOBA Patisserie & Bakery | Kebena',
                'short' => 'Kebena',
                'rating' => 4.4,
                'count' => 313,
                'maps_url' => null,
                'maps_query' => 'KOBA Patisserie & Bakery Kebena Shewareged Gedle St Addis Ababa',
                'reviews' => [
                    ['text' => 'One of the best pastry in Addis with nice decorations and good service !', 'author' => null, 'rating' => null, 'date' => null],
                ],
            ],
        ],

        /*
        | Reviews whose branch is not known. Shown mixed in with the listing
        | reviews above. Set 'listing' to a listing 'key' (atlas, 4-kilo,
        | kebena) to add the branch label and its Google Maps link.
        | photos: [file in public/images/koba/reviews, width, height] — the
        | reviewer's own photos from the review (reviewer-owned; get their OK).
        | date is left empty: Google only showed relative dates ("5 months ago").
        */
        'reviews' => [
            [
                'listing' => null,
                'author' => 'Zena Tadesse',
                'rating' => 5,
                'date' => null,
                'text' => 'My everyday morning stop over! An incredible place with an amazing breakfast and lunch. Menu is so unique and portion is generous. The waiting staff, supervisors and kitchen staff are amazing, they treat every customer as it’s the only. They cater to every regular customer  learning his/her  behavior. Pastry and bakery is amazing, so many choices. Ample parking space relatively from what the area provides.',
                'photos' => [['zena-1.jpg', 295, 220], ['zena-2.jpg', 294, 220]],
            ],
            [
                'listing' => null,
                'author' => 'betty eshete',
                'rating' => 4,
                'date' => null,
                // Truncated by Google ("… More") in the source — paste the full text here.
                'text' => 'We ordered a salmon sandwich along with a few desserts. The desserts arrived very quickly, but the salmon sandwich took quite a while to come out. …',
                'photos' => [['betty-1.jpg', 295, 360], ['betty-2.jpg', 292, 360]],
            ],
            [
                'listing' => null,
                'author' => 'c!',
                'rating' => 5,
                'date' => null,
                'text' => 'First food I had in Addis Ababa. The coffee was good. The french toast was big and fulfilling.',
                'photos' => [['c-1.jpg', 295, 402], ['c-2.jpg', 296, 220], ['c-3.jpg', 296, 171]],
            ],
        ],
    ],

    'email' => 'hello@kobapatisserie.com',

    // Replace with the official profile URLs.
    'social' => [
        'Instagram' => 'https://www.instagram.com/',
        'Facebook' => 'https://www.facebook.com/',
        'TikTok' => 'https://www.tiktok.com/',
    ],
];
