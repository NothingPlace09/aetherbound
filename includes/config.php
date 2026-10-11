<?php
// Main site settings. Edit these values, no other file needs to change.
return [
    'site_name'    => 'AetherboundSMP',
    'server_ip'    => 'play.aetherbound.bond',
    'bedrock_port' => '19132',
    'discord_url'  => 'https://discord.gg/aetherboundsmp',

    // Seconds the Discord member count is cached in /cache/discord.json
    'discord_cache_seconds' => 300,

    // Menu pages: key => [file, translation key]
    'pages' => [
        'home'  => ['index.php', 'nav_home'],
        'news'  => ['news.php',  'nav_news'],
        'rules' => ['rules.php', 'nav_rules'],
        'staff' => ['staff.php', 'nav_staff'],
        'apply' => ['apply.php', 'nav_apply'],
    ],

    // Admin account created automatically the first time the database is built.
    // Only the password HASH is stored here. To change the password, log in and use
    // "Change password" on the profile page (this value is only used for the first setup).
    'admin_seed' => [
        'username'      => 'AetherAdmin',
        'email'         => 'aetherboundadmin@gmail.com',
        'password_hash' => '$2y$10$5MnJadwKQUXFM9AbfhBgFuEjm5ueUXD0nHuJkTnHBqPzsk31TMz2O',
    ],

    // Profile picture = face of the member's Minecraft skin, looked up by username.
    // {name} = username, {size} = pixels. Any service with this URL shape works (e.g. https://minotar.net/helm/{name}/{size}.png).
    'skin_face_url' => 'https://mc-heads.net/avatar/{name}/{size}',

    // About-me text: max characters (it can contain formatting codes)
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
