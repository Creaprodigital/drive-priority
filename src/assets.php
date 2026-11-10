<?php
declare(strict_types=1);

/** L’empreinte dépend du contenu, même si le déploiement conserve les dates des fichiers. */
function assetUrl(string $path): string
{
    static $versions = [];
    if (!preg_match('~^/assets/[a-zA-Z0-9_-]+\.(?:webp|png|jpg|jpeg|svg|css|js)$~D', $path)) {
        throw new InvalidArgumentException('Chemin de ressource invalide');
    }
    if (!isset($versions[$path])) {
        $file = __DIR__ . '/..' . $path;
        if (!is_file($file)) throw new RuntimeException('Ressource absente : ' . $path);
        $versions[$path] = substr(hash_file('sha256', $file), 0, 20);
    }
    return $path . '?v=' . $versions[$path];
}

function renderedStylesheet(): string
{
    static $css;
    if ($css === null) {
        $css = preg_replace_callback(
            '~url\(([\'\"]?)(/assets/[a-zA-Z0-9_.-]+)\1\)~',
            fn(array $match): string => "url('" . assetUrl($match[2]) . "')",
            file_get_contents(__DIR__ . '/../assets/style.css')
        );
    }
    return $css;
}

function stylesheetVersion(): string
{
    return substr(hash('sha256', renderedStylesheet()), 0, 20);
}

function stylesheetUrl(): string
{
    return '/styles.css?v=' . stylesheetVersion();
}
