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

    // Profile picture = face of the member's Minecraft skin (see includes/skin.php and skin.php).
    // The server asks Mojang directly; these are the addresses it uses (you normally never change them).
    'skin_apis' => [
        'profile'        => ['https://api.mojang.com/users/profiles/minecraft/{name}', 'https://api.minecraftservices.com/minecraft/profile/lookup/name/{name}', 'https://playerdb.co/api/player/minecraft/{name}'],
        'session'        => 'https://sessionserver.mojang.com/session/minecraft/profile/{uuid}',
        'skin_fallbacks' => ['https://crafatar.com/skins/{uuid}', 'https://mc-heads.net/skin/{uuid}'],
    ],
    // Only used if the host has no PHP "gd" extension: public avatar service ({name} = the UUID, {size} = pixels)
    'skin_face_url' => 'https://mc-heads.net/avatar/{name}/{size}',

    // Staff applications are sent to a Discord channel through a webhook.
    // The webhook address is a SECRET (whoever has it can post in your channel): set it as the environment variable
    // APPLY_WEBHOOK_URL (Coolify -> your app -> Environment Variables). Never write it in a file that goes to GitHub.
    'apply_webhook'        => (string) getenv('APPLY_WEBHOOK_URL'),
    'apply_min_age'        => 14,   // younger applicants get an error
    'apply_cooldown_hours' => 24,   // the same member can apply for the same role again after this time
    'apply_daily_limit'    => 3,    // applications per member in 24 hours (all roles together)

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
