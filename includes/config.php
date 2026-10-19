<?php
return [
    'site_name'    => 'WaveMC',
    'server_ip'    => 'play.aetherbound.bond',
    'bedrock_port' => '19132',
    'discord_url'  => 'https://discord.gg/aetherboundsmp',

    'discord_cache_seconds' => 300,

    'pages' => [
        'home'  => ['home', 'nav_home'],
        'news'  => ['news',  'nav_news'],
        'rules' => ['rules', 'nav_rules'],
        'staff' => ['staff', 'nav_staff'],
        'apply' => ['apply', 'nav_apply'],
    ],

    'admin_seed' => [
        'username'      => 'AetherAdmin',
        'email'         => 'aetherboundadmin@gmail.com',
        'password_hash' => '$2y$10$5MnJadwKQUXFM9AbfhBgFuEjm5ueUXD0nHuJkTnHBqPzsk31TMz2O',
    ],

    'skin_apis' => [
        'profile'        => ['https://api.mojang.com/users/profiles/minecraft/{name}', 'https://api.minecraftservices.com/minecraft/profile/lookup/name/{name}', 'https://playerdb.co/api/player/minecraft/{name}'],
        'session'        => 'https://sessionserver.mojang.com/session/minecraft/profile/{uuid}',
        'skin_fallbacks' => ['https://crafatar.com/skins/{uuid}', 'https://mc-heads.net/skin/{uuid}'],
    ],
    'skin_face_url' => 'https://mc-heads.net/avatar/{name}/{size}',

    'apply_webhook'        => (string) getenv('APPLY_WEBHOOK_URL'),
    'apply_min_age'        => 14,
    'apply_cooldown_hours' => 24,
    'apply_daily_limit'    => 3,

    'about_max' => 1500,

    'default_lang' => 'en',
    'languages' => [
        'en' => ['name' => 'English',  'code' => 'EN'],
        'it' => ['name' => 'Italiano', 'code' => 'IT'],
        'fr' => ['name' => 'Français', 'code' => 'FR'],
        'de' => ['name' => 'Deutsch',  'code' => 'DE'],
        'es' => ['name' => 'Español',  'code' => 'ES'],
    ],
];
