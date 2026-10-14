<?php
// The staff roles people can apply for, with their requirements and extra questions.
// Edit this file to add/remove roles or change questions: no other file needs to change.
//
// L('English', 'Italiano') = a text in two languages. **bold** highlights key words.
// 'questions' = extra questions of the role (the general ones - nickname, age, time zone, availability, about you - are always asked).
//   type 'text' = one line (max 200 characters), 'textarea' = long answer (max 700), 'min' = minimum characters, 'required' = true/false.

if (!function_exists('L')) {
    function L($en, $it = null)
    {
        return ['en' => $en, 'it' => $it !== null ? $it : $en];
    }
}

/* Text of the visitor's language (English if there is no translation). */
function ap_t($v)
{
    return is_array($v) ? rules_pick($v['en'], $v['it']) : (string) $v;
}

function apply_roles()
{
    $common = [
        L('Be at least **14** years old.', 'Avere almeno **14** anni.'),
        L('Own Minecraft **Premium**.', 'Possedere Minecraft **Premium**.'),
        L('Have a good knowledge of the **Server Rules**.', 'Conoscere bene il **Regolamento** del server.'),
        L('Do **NOT** be a **Staff Member** on other servers.', '**NON** essere **Staff** su altri server.'),
        L('**NOT** having received any **Sanctions** in the last **30 days**.', '**NON** aver ricevuto **sanzioni** negli ultimi **30 giorni**.'),
    ];
    $why = function ($roleEn, $roleIt) {
        return ['id' => 'why', 'type' => 'textarea', 'required' => true, 'min' => 30, 'max' => 700,
            'label' => L('Why do you want to be a ' . $roleEn . '?', 'Perché vuoi diventare ' . $roleIt . '?'), 'help' => null];
    };

    return [
        [
            'id' => 'helper', 'icon' => 'bell',
            'title' => L('Helper Supporter', 'Helper Supporter'), 'tag' => L('First line', 'Prima linea'),
            'desc' => L('As a **Helper**, your main job is to support our community and keep the server a fun place for everyone.', 'Come **Helper** il tuo compito principale è aiutare la community e mantenere il server un posto divertente per tutti.'),
            'req' => array_merge([L('Have a good knowledge of **English** or **Italian**.', 'Avere una buona conoscenza di **inglese** o **italiano**.')], $common),
            'questions' => [
                ['id' => 'exp', 'type' => 'textarea', 'required' => false, 'min' => 0, 'max' => 700,
                    'label' => L('Previous staff experience', 'Esperienze precedenti nello staff'), 'help' => L('Servers, roles and for how long. Write "None" if you have none.', 'Server, ruoli e per quanto tempo. Scrivi "Nessuna" se non ne hai.')],
                ['id' => 'case', 'type' => 'textarea', 'required' => true, 'min' => 30, 'max' => 700,
                    'label' => L('A player keeps insulting others in chat. What do you do?', 'Un giocatore insulta continuamente gli altri in chat. Cosa fai?'), 'help' => null],
                $why('Helper', 'Helper'),
            ],
        ],
        [
            'id' => 'chatmod', 'icon' => 'heart',
            'title' => L('Chat Moderator', 'Chat Moderator'), 'tag' => L('Chat', 'Chat'),
            'desc' => L('As a **ChatMod**, you are the guardian of our chat: you keep it clean, friendly and free of spam.', 'Come **ChatMod** sei il guardiano della nostra chat: la mantieni pulita, amichevole e senza spam.'),
            'req' => array_merge([L('Have a good knowledge of **English** or **Italian**.', 'Avere una buona conoscenza di **inglese** o **italiano**.'), L('Be **active** every day on Discord and in game.', 'Essere **attivo** ogni giorno su Discord e in gioco.')], $common),
            'questions' => [
                ['id' => 'exp', 'type' => 'textarea', 'required' => false, 'min' => 0, 'max' => 700,
                    'label' => L('Previous moderation experience', 'Esperienze precedenti di moderazione'), 'help' => L('Write "None" if you have none.', 'Scrivi "Nessuna" se non ne hai.')],
                ['id' => 'case', 'type' => 'textarea', 'required' => true, 'min' => 30, 'max' => 700,
                    'label' => L('Someone posts ads for another server in the chat. What do you do?', 'Qualcuno pubblicizza un altro server in chat. Cosa fai?'), 'help' => null],
                $why('Chat Moderator', 'Chat Moderator'),
            ],
        ],
        [
            'id' => 'screenshare', 'icon' => 'shield',
            'title' => L('Helper Screenshare', 'Helper Screenshare'), 'tag' => L('Screenshare', 'Screenshare'),
            'desc' => L('As a **ScreenShare Helper**, your mission is to ensure fair play by hunting down cheaters with screenshare checks.', 'Come **ScreenShare Helper** la tua missione è garantire il fair play scovando i cheater con i controlli screenshare.'),
            'req' => array_merge([L('Have a working **Microphone** and be able to **Record**.', 'Avere un **microfono** funzionante ed essere in grado di **registrare**.'), L('Have a good knowledge of **English** or **Italian**.', 'Avere una buona conoscenza di **inglese** o **italiano**.')], $common),
            'questions' => [
                ['id' => 'tools', 'type' => 'textarea', 'required' => true, 'min' => 20, 'max' => 700,
                    'label' => L('Which cheats and screenshare tools do you know?', 'Quali cheat e strumenti di screenshare conosci?'), 'help' => L('For example how you check a suspected player.', 'Per esempio come controlli un giocatore sospetto.')],
                ['id' => 'exp', 'type' => 'textarea', 'required' => false, 'min' => 0, 'max' => 700,
                    'label' => L('Previous screenshare experience', 'Esperienze precedenti di screenshare'), 'help' => L('Write "None" if you have none.', 'Scrivi "Nessuna" se non ne hai.')],
                $why('ScreenShare Helper', 'ScreenShare Helper'),
            ],
        ],
        [
            'id' => 'developer', 'icon' => 'code',
            'title' => L('Java Developer', 'Java Developer'), 'tag' => L('Plugin & Backend', 'Plugin e Backend'),
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
                ['id' => 'exp', 'type' => 'textarea', 'required' => true, 'min' => 20, 'max' => 700,
                    'label' => L('Experience & Languages', 'Esperienza e linguaggi'), 'help' => L('How long you code and which languages, APIs and databases you know.', 'Da quanto programmi e quali linguaggi, API e database conosci.')],
                ['id' => 'portfolio', 'type' => 'text', 'required' => false, 'min' => 0, 'max' => 200,
                    'label' => L('Portfolio / GitHub', 'Portfolio / GitHub'), 'help' => L('A link to your work. Write "None" if you have none.', 'Un link ai tuoi lavori. Scrivi "Nessuno" se non ne hai.')],
                $why('Developer', 'Developer'),
            ],
        ],
        [
            'id' => 'builder', 'icon' => 'cube',
            'title' => L('Builder', 'Builder'), 'tag' => L('Building', 'Costruzioni'),
            'desc' => L('As a **Builder**, you shape the world of the server: spawns, lobbies, maps and decorations.', 'Come **Builder** dai forma al mondo del server: spawn, lobby, mappe e decorazioni.'),
            'req' => [
                L('Be at least **14** years old.', 'Avere almeno **14** anni.'),
                L('Own Minecraft **Premium**.', 'Possedere Minecraft **Premium**.'),
                L('Know tools such as **WorldEdit** (or similar).', 'Conoscere strumenti come **WorldEdit** (o simili).'),
                L('Be able to show **screenshots** or a **portfolio** of your builds.', 'Saper mostrare **screenshot** o un **portfolio** delle tue costruzioni.'),
                L('Do **NOT** be a **Staff Member** on other servers.', '**NON** essere **Staff** su altri server.'),
            ],
            'questions' => [
                ['id' => 'portfolio', 'type' => 'text', 'required' => true, 'min' => 3, 'max' => 200,
                    'label' => L('Portfolio / screenshots', 'Portfolio / screenshot'), 'help' => L('A link to images of your builds (Imgur, Discord, Google Drive...).', 'Un link a immagini delle tue costruzioni (Imgur, Discord, Google Drive...).')],
                ['id' => 'styles', 'type' => 'textarea', 'required' => true, 'min' => 20, 'max' => 700,
                    'label' => L('Which styles do you build and with which tools?', 'Quali stili costruisci e con quali strumenti?'), 'help' => null],
                $why('Builder', 'Builder'),
            ],
        ],
    ];
}

function apply_find_role($id)
{
    foreach (apply_roles() as $r) {
        if ($r['id'] === $id) {
            return $r;
        }
    }
    return null;
}

/* Rules shown to everyone who applies. */
function apply_rules()
{
    return [
        L('Applications must be written in **English** or **Italian**. Other languages are automatically **Rejected**.', 'Le candidature vanno scritte in **inglese** o **italiano**. Le altre lingue vengono **Rifiutate** automaticamente.'),
        L('Answer every question in **detail**. Applications with superficial answers will be **Rejected**.', 'Rispondi a ogni domanda in **modo dettagliato**. Le candidature con risposte superficiali verranno **Rifiutate**.'),
        L('It is **prohibited** to ask about the **status** of your application. Any inquiry will result in it being **Rejected**.', 'È **vietato** chiedere lo **stato** della tua candidatura. Ogni richiesta comporta il **Rifiuto** della candidatura.'),
    ];
}
