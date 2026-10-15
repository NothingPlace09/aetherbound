<?php
if (!function_exists('L')) {
    function L($en, $it = null)
    {
        return ['en' => $en, 'it' => $it !== null ? $it : $en];
    }
}

function rl($v)
{
    if (!is_array($v)) {
        return (string) $v;
    }
    $lang = isset($GLOBALS['lang']) ? $GLOBALS['lang'] : 'en';
    return isset($v[$lang]) ? $v[$lang] : $v['en'];
}

function rules_seed_data()
{
    return [
        [
            'label' => L('Platforms', 'Piattaforme'),
            'items' => [
                [
                    'id' => 'discord', 'icon' => 'chat',
                    'title' => L('Discord', 'Discord'),
                    'sub' => L('These rules apply to our Discord server.', 'Queste regole valgono per il nostro server Discord.'),
                    'updated' => '2026-10-12',
                    'sections' => [
                        [
                            'title' => L('Respect', 'Rispetto'),
                            'rules' => [
                                ['text' => L('Treat everyone with **respect**. Insults, harassment and provocations are not tolerated.', 'Tratta tutti con **rispetto**. Insulti, molestie e provocazioni non sono tollerati.')],
                                ['text' => L('**Hate speech**, discrimination and threats are forbidden and lead to a heavy penalty.', '**Discorsi d\'odio**, discriminazioni e minacce sono vietati e portano a una sanzione pesante.')],
                                ['text' => L('Do **NOT** post NSFW, gore or shocking content in any channel.', '**NON** pubblicare contenuti NSFW, gore o scioccanti in nessun canale.')],
                            ],
                        ],
                        [
                            'title' => L('Chat & channels', 'Chat e canali'),
                            'rules' => [
                                ['text' => L('Use each channel for its **topic**. Write in the language of the channel.', 'Usa ogni canale per il suo **argomento**. Scrivi nella lingua del canale.')],
                                ['text' => L('No **spam**, flooding, excessive caps or mass mentions.', 'Niente **spam**, flood, maiuscole eccessive o menzioni di massa.')],
                                ['text' => L('**Advertising** other servers, stores or social profiles is not allowed without staff permission.', 'La **pubblicità** di altri server, store o profili social non è permessa senza il permesso dello staff.')],
                            ],
                        ],
                        [
                            'title' => L('Privacy & staff', 'Privacy e staff'),
                            'rules' => [
                                ['title' => L('Protect personal data', 'Proteggi i dati personali'), 'text' => L('Never share **personal information** (yours or of others), and do not leak private conversations.', 'Non condividere mai **informazioni personali** (tue o di altri) e non diffondere conversazioni private.')],
                                ['title' => L('Listen to the staff', 'Ascolta lo staff'), 'text' => L('Follow the **instructions** of the staff. If you disagree, open a **ticket** instead of arguing in the chat.', 'Segui le **indicazioni** dello staff. Se non sei d\'accordo, apri un **ticket** invece di discutere in chat.')],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'label' => L('Durations', 'Durate'),
            'items' => [
                [
                    'id' => 'ban', 'icon' => 'ban',
                    'title' => L('Ban', 'Ban'),
                    'sub' => L('Standard durations for bans. The staff may adjust them in special cases.', 'Durate standard dei ban. Lo staff può modificarle in casi particolari.'),
                    'updated' => '2026-10-12',
                    'sections' => [
                        [
                            'title' => L('Ban durations', 'Durata dei ban'),
                            'table' => [
                                'head' => [L('Offense', 'Infrazione'), L('1st time', '1ª volta'), L('2nd time', '2ª volta'), L('3rd time', '3ª volta')],
                                'rows' => [
                                    [L('Hacked client / cheating', 'Hack client / cheat'), L('30 days', '30 giorni'), L('90 days', '90 giorni'), L('Permanent', 'Permanente')],
                                    [L('Bug / exploit abuse', 'Abuso di bug / exploit'), L('7 days', '7 giorni'), L('30 days', '30 giorni'), L('Permanent', 'Permanente')],
                                    [L('Griefing / stealing', 'Grief / furti'), L('7 days', '7 giorni'), L('30 days', '30 giorni'), L('Permanent', 'Permanente')],
                                    [L('Ban evasion (alt account)', 'Evasione del ban (account alt)'), L('Permanent', 'Permanente'), L('Permanent', 'Permanente'), L('Permanent', 'Permanente')],
                                ],
                            ],
                        ],
                        [
                            'title' => L('Notes', 'Note'),
                            'rules' => [
                                ['text' => L('Durations **increase** with every repeated offense.', 'Le durate **aumentano** a ogni recidiva.')],
                                ['text' => L('To appeal a ban, open a **ticket** on our Discord server.', 'Per contestare un ban, apri un **ticket** sul nostro server Discord.')],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'mute', 'icon' => 'mute',
                    'title' => L('Mute', 'Mute'),
                    'sub' => L('Standard durations for mutes in the game chat and on Discord.', 'Durate standard dei mute nella chat di gioco e su Discord.'),
                    'updated' => '2026-10-12',
                    'sections' => [
                        [
                            'title' => L('Mute durations', 'Durata dei mute'),
                            'table' => [
                                'head' => [L('Offense', 'Infrazione'), L('1st time', '1ª volta'), L('2nd time', '2ª volta'), L('3rd time', '3ª volta')],
                                'rows' => [
                                    [L('Spam / flood', 'Spam / flood'), L('1 hour', '1 ora'), L('6 hours', '6 ore'), L('24 hours', '24 ore')],
                                    [L('Insults / provocation', 'Insulti / provocazioni'), L('3 hours', '3 ore'), L('12 hours', '12 ore'), L('3 days', '3 giorni')],
                                    [L('Advertising', 'Pubblicità'), L('1 day', '1 giorno'), L('7 days', '7 giorni'), L('Permanent', 'Permanente')],
                                    [L('Hate speech / threats', 'Discorsi d\'odio / minacce'), L('7 days', '7 giorni'), L('30 days', '30 giorni'), L('Permanent', 'Permanente')],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'label' => L('Minecraft', 'Minecraft'),
            'items' => [
                [
                    'id' => 'global', 'icon' => 'globe',
                    'title' => L('Global', 'Globale'),
                    'sub' => L('These rules apply everywhere on the Minecraft server.', 'Queste regole valgono ovunque sul server Minecraft.'),
                    'updated' => '2026-10-12',
                    'sections' => [
                        [
                            'title' => L('Fair play', 'Fair play'),
                            'rules' => [
                                ['text' => L('**Hacked clients**, x-ray, auto-clickers and any other unfair advantage are forbidden.', '**Client modificati**, x-ray, auto-clicker e qualsiasi altro vantaggio sleale sono vietati.')],
                                ['text' => L('Do **NOT** abuse bugs or exploits. **Report** them to the staff instead.', '**NON** abusare di bug o exploit. **Segnalali** invece allo staff.')],
                                ['text' => L('Cosmetic and performance mods are fine. If you are not sure about a mod, **ask first**.', 'Le mod estetiche e di performance vanno bene. Se non sei sicuro di una mod, **chiedi prima**.')],
                            ],
                        ],
                        [
                            'title' => L('Chat & names', 'Chat e nomi'),
                            'rules' => [
                                ['text' => L('Follow the **Discord** chat rules in the game chat too.', 'Segui le regole della chat di **Discord** anche nella chat di gioco.')],
                                ['text' => L('Usernames and skins must **NOT** be offensive or inappropriate.', 'Nomi utente e skin **NON** devono essere offensivi o inappropriati.')],
                            ],
                        ],
                        [
                            'title' => L('Account', 'Account'),
                            'rules' => [
                                ['title' => L('Your account, your responsibility', 'Il tuo account, la tua responsabilità'), 'text' => L('You are responsible for **ALL** actions done with your account. Giving it to someone else does **NOT** remove your responsibility.', 'Sei responsabile di **TUTTE** le azioni fatte con il tuo account. Darlo a qualcun altro **NON** ti toglie la responsabilità.')],
                                ['title' => L('Keep it secure', 'Tienilo al sicuro'), 'text' => L('Never share your login details. The staff will **never** ask for your password.', 'Non condividere mai i tuoi dati di accesso. Lo staff non ti chiederà **mai** la password.')],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'smp', 'icon' => 'cube',
                    'title' => L('SMP', 'SMP'),
                    'sub' => L('The survival world: how to play fair with everyone.', 'Il mondo survival: come giocare correttamente con tutti.'),
                    'updated' => '2026-10-12',
                    'sections' => [
                        [
                            'title' => L('Builds & protection', 'Costruzioni e protezione'),
                            'rules' => [
                                ['text' => L('**Griefing**, stealing and destroying the builds of others is forbidden.', '**Grief**, furti e distruzione delle costruzioni altrui sono vietati.')],
                                ['text' => L('Do **NOT** build inside or too close to the claims of other players.', '**NON** costruire dentro o troppo vicino ai claim degli altri giocatori.')],
                                ['text' => L('Trapping, killing or tricking players into **unprotected** areas to rob them is **NOT** allowed.', 'Intrappolare, uccidere o attirare giocatori in zone **non protette** per derubarli **NON** è permesso.')],
                            ],
                        ],
                        [
                            'title' => L('PvP', 'PvP'),
                            'rules' => [
                                ['text' => L('PvP is allowed only with the **consent** of both players, or in the areas marked for PvP.', 'Il PvP è permesso solo con il **consenso** di entrambi i giocatori, o nelle zone segnate per il PvP.')],
                                ['text' => L('**Spawn killing** and repeated harassment of the same player are forbidden.', 'Lo **spawn kill** e le molestie ripetute allo stesso giocatore sono vietati.')],
                            ],
                        ],
                        [
                            'title' => L('Farms & lag', 'Farm e lag'),
                            'rules' => [
                                ['text' => L('Do **NOT** build machines or farms made to **lag** the server. The staff may remove them.', '**NON** costruire macchine o farm fatte per far **laggare** il server. Lo staff può rimuoverle.')],
                                ['text' => L('Trades with **real money** and scams between players are forbidden.', 'Scambi con **denaro reale** e truffe tra giocatori sono vietati.')],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}
