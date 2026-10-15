<?php
if (!function_exists('L')) {
    function L($en, $it = null)
    {
        return ['en' => $en, 'it' => $it !== null ? $it : $en];
    }
}

function apply_seed_data()
{
    $common = [
        L('Be at least **14** years old.', 'Avere almeno **14** anni.'),
        L('Own Minecraft **Premium**.', 'Possedere Minecraft **Premium**.'),
        L('Have a good knowledge of the **Server Rules**.', 'Conoscere bene il **Regolamento** del server.'),
        L('Do **NOT** be a **Staff Member** on other servers.', '**NON** essere **Staff** su altri server.'),
        L('**NOT** having received any **Sanctions** in the last **30 days**.', '**NON** aver ricevuto **sanzioni** negli ultimi **30 giorni**.'),
    ];
    $lang = L('Have a good knowledge of **English** or **Italian**.', 'Avere una buona conoscenza di **inglese** o **italiano**.');
    $why = function ($roleEn, $roleIt) {
        return ['type' => 'textarea', 'required' => 1, 'min' => 30, 'max' => 700,
            'label' => L('Why do you want to be a ' . $roleEn . '?', 'Perché vuoi diventare ' . $roleIt . '?'), 'help' => null];
    };
    $creatorWhy = ['type' => 'textarea', 'required' => 1, 'min' => 30, 'max' => 700,
        'label' => L('Why do you want to partner with us?', 'Perché vuoi collaborare con noi?'), 'help' => null];
    $channel = function ($what) {
        return ['type' => 'text', 'required' => 1, 'min' => 5, 'max' => 200,
            'label' => L('Link to your ' . $what, 'Link al tuo ' . $what), 'help' => null];
    };
    $followers = function ($what) {
        return ['type' => 'text', 'required' => 1, 'min' => 1, 'max' => 100,
            'label' => L('How many ' . $what . ' do you have?', 'Quanti ' . $what . ' hai?'), 'help' => null];
    };
    $views = ['type' => 'text', 'required' => 0, 'min' => 0, 'max' => 100,
        'label' => L('Average views / viewers', 'Visualizzazioni / spettatori medi'), 'help' => L('Write "Unknown" if you do not know.', 'Scrivi "Non so" se non lo sai.')];

    return [
        'rules' => [
            L('Applications must be written in **English** or **Italian**. Other languages are automatically **Rejected**.', 'Le candidature vanno scritte in **inglese** o **italiano**. Le altre lingue vengono **Rifiutate** automaticamente.'),
            L('Answer every question in **detail**. Applications with superficial answers will be **Rejected**.', 'Rispondi a ogni domanda in **modo dettagliato**. Le candidature con risposte superficiali verranno **Rifiutate**.'),
            L('It is **prohibited** to ask about the **status** of your application. Any inquiry will result in it being **Rejected**.', 'È **vietato** chiedere lo **stato** della tua candidatura. Ogni richiesta comporta il **Rifiuto** della candidatura.'),
        ],
        'cats' => [
            [
                'slug' => 'staff', 'icon' => 'users',
                'short' => L('Staff', 'Staff'), 'title' => L('Staff Applications', 'Candidature Staff'), 'tag' => L('Internal team', 'Team interno'),
                'desc' => L('Join our team! We are looking for supporters, screensharers, developers and builders.', 'Unisciti al nostro team! Cerchiamo supporter, screensharer, developer e builder.'),
                'roles' => [
                    [
                        'slug' => 'helper', 'icon' => 'bell', 'title' => L('Helper Supporter', 'Helper Supporter'), 'tag' => L('First line', 'Prima linea'),
                        'desc' => L('As a **Helper**, your main job is to support our community and keep the server a fun place for everyone.', 'Come **Helper** il tuo compito principale è aiutare la community e mantenere il server un posto divertente per tutti.'),
                        'req' => array_merge([$lang], $common),
                        'questions' => [
                            ['type' => 'textarea', 'required' => 0, 'min' => 0, 'max' => 700, 'label' => L('Previous staff experience', 'Esperienze precedenti nello staff'), 'help' => L('Servers, roles and for how long. Write "None" if you have none.', 'Server, ruoli e per quanto tempo. Scrivi "Nessuna" se non ne hai.')],
                            ['type' => 'textarea', 'required' => 1, 'min' => 30, 'max' => 700, 'label' => L('A player keeps insulting others in chat. What do you do?', 'Un giocatore insulta continuamente gli altri in chat. Cosa fai?'), 'help' => null],
                            $why('Helper', 'Helper'),
                        ],
                    ],
                    [
                        'slug' => 'chatmod', 'icon' => 'heart', 'title' => L('Chat Moderator', 'Chat Moderator'), 'tag' => L('Chat', 'Chat'),
                        'desc' => L('As a **ChatMod**, you are the guardian of our chat: you keep it clean, friendly and free of spam.', 'Come **ChatMod** sei il guardiano della nostra chat: la mantieni pulita, amichevole e senza spam.'),
                        'req' => array_merge([$lang, L('Be **active** every day on Discord and in game.', 'Essere **attivo** ogni giorno su Discord e in gioco.')], $common),
                        'questions' => [
                            ['type' => 'textarea', 'required' => 0, 'min' => 0, 'max' => 700, 'label' => L('Previous moderation experience', 'Esperienze precedenti di moderazione'), 'help' => L('Write "None" if you have none.', 'Scrivi "Nessuna" se non ne hai.')],
                            ['type' => 'textarea', 'required' => 1, 'min' => 30, 'max' => 700, 'label' => L('Someone posts ads for another server in the chat. What do you do?', 'Qualcuno pubblicizza un altro server in chat. Cosa fai?'), 'help' => null],
                            $why('Chat Moderator', 'Chat Moderator'),
                        ],
                    ],
                    [
                        'slug' => 'screenshare', 'icon' => 'shield', 'title' => L('Helper Screenshare', 'Helper Screenshare'), 'tag' => L('Screenshare', 'Screenshare'),
                        'desc' => L('As a **ScreenShare Helper**, your mission is to ensure fair play by hunting down cheaters with screenshare checks.', 'Come **ScreenShare Helper** la tua missione è garantire il fair play scovando i cheater con i controlli screenshare.'),
                        'req' => array_merge([L('Have a working **Microphone** and be able to **Record**.', 'Avere un **microfono** funzionante ed essere in grado di **registrare**.'), $lang], $common),
                        'questions' => [
                            ['type' => 'textarea', 'required' => 1, 'min' => 20, 'max' => 700, 'label' => L('Which cheats and screenshare tools do you know?', 'Quali cheat e strumenti di screenshare conosci?'), 'help' => L('For example how you check a suspected player.', 'Per esempio come controlli un giocatore sospetto.')],
                            ['type' => 'textarea', 'required' => 0, 'min' => 0, 'max' => 700, 'label' => L('Previous screenshare experience', 'Esperienze precedenti di screenshare'), 'help' => L('Write "None" if you have none.', 'Scrivi "Nessuna" se non ne hai.')],
                            $why('ScreenShare Helper', 'ScreenShare Helper'),
                        ],
                    ],
                    [
                        'slug' => 'developer', 'icon' => 'code', 'title' => L('Java Developer', 'Java Developer'), 'tag' => L('Plugin & Backend', 'Plugin e Backend'),
                        'desc' => L('As a **Developer**, you are the brain behind the server mechanics: you bring our ideas to life and keep everything running smoothly.', 'Come **Developer** sei il cervello delle meccaniche del server: realizzi le nostre idee e fai funzionare tutto senza intoppi.'),
                        'req' => [
                            L('Be at least **14** years old.', 'Avere almeno **14** anni.'),
                            L('Have a working **Microphone**.', 'Avere un **microfono** funzionante.'),
                            L('Strong **teamwork** and **collaboration** skills.', 'Ottime capacità di **lavoro di squadra** e **collaborazione**.'),
                            L('Do **NOT** be a **Staff Member** on other servers.', '**NON** essere **Staff** su altri server.'),
                            L('Have at least **2 years** of experience in **Java**.', 'Avere almeno **2 anni** di esperienza in **Java**.'),
                            L('Have experience with **GitHub** and **Git**.', 'Avere esperienza con **GitHub** e **Git**.'),
                            L('Have experience with **Paper API** (and ideally **Velocity API**).', 'Avere esperienza con **Paper API** (e meglio ancora **Velocity API**).'),
                        ],
                        'questions' => [
                            ['type' => 'textarea', 'required' => 1, 'min' => 20, 'max' => 700, 'label' => L('Experience & Languages', 'Esperienza e linguaggi'), 'help' => L('How long you code and which languages, APIs and databases you know.', 'Da quanto programmi e quali linguaggi, API e database conosci.')],
                            ['type' => 'text', 'required' => 0, 'min' => 0, 'max' => 200, 'label' => L('Portfolio / GitHub', 'Portfolio / GitHub'), 'help' => L('A link to your work. Write "None" if you have none.', 'Un link ai tuoi lavori. Scrivi "Nessuno" se non ne hai.')],
                            $why('Developer', 'Developer'),
                        ],
                    ],
                    [
                        'slug' => 'builder', 'icon' => 'cube', 'title' => L('Builder', 'Builder'), 'tag' => L('Building', 'Costruzioni'),
                        'desc' => L('As a **Builder**, you shape the world of the server: spawns, lobbies, maps and decorations.', 'Come **Builder** dai forma al mondo del server: spawn, lobby, mappe e decorazioni.'),
                        'req' => [
                            L('Be at least **14** years old.', 'Avere almeno **14** anni.'),
                            L('Own Minecraft **Premium**.', 'Possedere Minecraft **Premium**.'),
                            L('Know tools such as **WorldEdit** (or similar).', 'Conoscere strumenti come **WorldEdit** (o simili).'),
                            L('Be able to show **screenshots** or a **portfolio** of your builds.', 'Saper mostrare **screenshot** o un **portfolio** delle tue costruzioni.'),
                            L('Do **NOT** be a **Staff Member** on other servers.', '**NON** essere **Staff** su altri server.'),
                        ],
                        'questions' => [
                            ['type' => 'text', 'required' => 1, 'min' => 3, 'max' => 200, 'label' => L('Portfolio / screenshots', 'Portfolio / screenshot'), 'help' => L('A link to images of your builds (Imgur, Discord, Google Drive...).', 'Un link a immagini delle tue costruzioni (Imgur, Discord, Google Drive...).')],
                            ['type' => 'textarea', 'required' => 1, 'min' => 20, 'max' => 700, 'label' => L('Which styles do you build and with which tools?', 'Quali stili costruisci e con quali strumenti?'), 'help' => null],
                            $why('Builder', 'Builder'),
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'creator', 'icon' => 'monitor',
                'short' => L('Creator', 'Creator'), 'title' => L('Creator Applications', 'Candidature Creator'), 'tag' => L('Media partners', 'Partner media'),
                'desc' => L('Are you a Content Creator? Partner with us on TikTok, Twitch, YouTube or as Media.', 'Sei un Content Creator? Collabora con noi su TikTok, Twitch, YouTube o come Media.'),
                'roles' => [
                    [
                        'slug' => 'youtuber', 'icon' => 'monitor', 'title' => L('YouTube Creator', 'YouTube Creator'), 'tag' => L('YouTube', 'YouTube'),
                        'desc' => L('Make videos about our server and grow the community on **YouTube**.', 'Crea video sul nostro server e fai crescere la community su **YouTube**.'),
                        'req' => [
                            L('Have an **active** YouTube channel with Minecraft content.', 'Avere un canale YouTube **attivo** con contenuti su Minecraft.'),
                            L('Have posted at least **1 video** in the last **30 days**.', 'Aver pubblicato almeno **1 video** negli ultimi **30 giorni**.'),
                            L('Be at least **14** years old.', 'Avere almeno **14** anni.'),
                            L('Respect the **Server Rules** on camera too.', 'Rispettare il **Regolamento** anche davanti alla camera.'),
                        ],
                        'questions' => [$channel('YouTube channel', 'canale YouTube'), $followers('subscribers', 'iscritti'), $views, $creatorWhy],
                    ],
                    [
                        'slug' => 'streamer', 'icon' => 'monitor', 'title' => L('Twitch Streamer', 'Twitch Streamer'), 'tag' => L('Twitch', 'Twitch'),
                        'desc' => L('Stream on our server and bring your viewers to play with us on **Twitch**.', 'Fai live sul nostro server e porta i tuoi spettatori a giocare con noi su **Twitch**.'),
                        'req' => [
                            L('Have an **active** Twitch channel that streams Minecraft.', 'Avere un canale Twitch **attivo** che fa live di Minecraft.'),
                            L('Have streamed at least **once** in the last **30 days**.', 'Aver fatto live almeno **una volta** negli ultimi **30 giorni**.'),
                            L('Be at least **14** years old.', 'Avere almeno **14** anni.'),
                            L('Respect the **Server Rules** during your streams.', 'Rispettare il **Regolamento** durante le tue live.'),
                        ],
                        'questions' => [$channel('Twitch channel', 'canale Twitch'), $followers('followers', 'follower'), $views, $creatorWhy],
                    ],
                    [
                        'slug' => 'tiktoker', 'icon' => 'monitor', 'title' => L('TikTok Creator', 'TikTok Creator'), 'tag' => L('TikTok', 'TikTok'),
                        'desc' => L('Share short videos about our server and reach new players on **TikTok**.', 'Condividi video brevi sul nostro server e raggiungi nuovi giocatori su **TikTok**.'),
                        'req' => [
                            L('Have an **active** TikTok account with Minecraft content.', 'Avere un account TikTok **attivo** con contenuti su Minecraft.'),
                            L('Have posted at least **3 videos** in the last **30 days**.', 'Aver pubblicato almeno **3 video** negli ultimi **30 giorni**.'),
                            L('Be at least **14** years old.', 'Avere almeno **14** anni.'),
                            L('Respect the **Server Rules** in your videos.', 'Rispettare il **Regolamento** nei tuoi video.'),
                        ],
                        'questions' => [$channel('TikTok profile', 'profilo TikTok'), $followers('followers', 'follower'), $views, $creatorWhy],
                    ],
                ],
            ],
        ],
    ];
}
