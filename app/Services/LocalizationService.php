<?php

namespace App\Services;

class LocalizationService
{
    protected array $rtlLanguages = [
        'ar', 'arc', 'dv', 'fa', 'ha', 'he', 'khw', 'ks', 'ku', 'ps',
        'ur', 'yi', 'ug', 'sd', 'syr', 'dhv', 'sqr', 'sam', 'man', 'men',
        'nqo', 'phn', 'th',
    ];

    protected array $emojiLanguages = [
        'af' => '🇿🇦',
        'sq' => '🇦🇱',
        'am' => '🇪🇹',
        'ar' => '🇸🇦',
        'hy' => '🇦🇲',
        'az' => '🇦🇿',
        'eu' => '🇪🇸',
        'be' => '🇧🇾',
        'bn' => '🇧🇩',
        'bs' => '🇧🇦',
        'bg' => '🇧🇬',
        'ca' => '🇪🇸',
        'zh' => '🇨🇳',
        'hr' => '🇭🇷',
        'cs' => '🇨🇿',
        'da' => '🇩🇰',
        'nl' => '🇳🇱',
        'en' => '🇺🇸',
        'et' => '🇪🇪',
        'fi' => '🇫🇮',
        'fr' => '🇫🇷',
        'gl' => '🇪🇸',
        'ka' => '🇬🇪',
        'de' => '🇩🇪',
        'el' => '🇬🇷',
        'gu' => '🇮🇳',
        'ht' => '🇭🇹',
        'he' => '🇮🇱',
        'hi' => '🇮🇳',
        'hu' => '🇭🇺',
        'is' => '🇮🇸',
        'id' => '🇮🇩',
        'ga' => '🇮🇪',
        'it' => '🇮🇹',
        'ja' => '🇯🇵',
        'kk' => '🇰🇿',
        'ko' => '🇰🇷',
        'lv' => '🇱🇻',
        'lt' => '🇱🇹',
        'mk' => '🇲🇰',
        'ms' => '🇲🇾',
        'ml' => '🇮🇳',
        'mt' => '🇲🇹',
        'mn' => '🇲🇳',
        'no' => '🇳🇴',
        'ps' => '🇦🇫',
        'fa' => '🇮🇷',
        'pl' => '🇵🇱',
        'pt' => '🇵🇹',
        'ro' => '🇷🇴',
        'ru' => '🇷🇺',
        'sr' => '🇷🇸',
        'sk' => '🇸🇰',
        'sl' => '🇸🇮',
        'es' => '🇪🇸',
        'sw' => '🇰🇪',
        'sv' => '🇸🇪',
        'ta' => '🇮🇳',
        'te' => '🇮🇳',
        'th' => '🇹🇭',
        'tr' => '🇹🇷',
        'uk' => '🇺🇦',
        'ur' => '🇵🇰',
        'uz' => '🇺🇿',
        'vi' => '🇻🇳',
        'cy' => '🇬🇧',
    ];

    public function isRtl(string $langCode): bool
    {
        return in_array(strtolower($langCode), $this->rtlLanguages, true);
    }

    public function getEmojiByCode(string $lang): string
    {
        $code = strtolower($lang);

        return $this->emojiLanguages[$code] ?? '❓';
    }
}
