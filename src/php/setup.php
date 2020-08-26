<?php

/**
 * Copyright 2020 Markus Leupold-Löwenthal
 *
 * This file is part of wiki.md-theme-sunset (Sunset).
 *
 * Sunset is free software: you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * Sunset is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Sunset. If not, see <https://www.gnu.org/licenses/>.
 */

// --- setup I18N --------------------------------------------------------------

require_once('core/Translate.php');
at\nerdreich\Translate::loadLanguage(dirname(__FILE__) . '/I18N/' . $config['language'] . '.yaml');

// --- register theme macros ---------------------------------------------------

$wiki->registerMacro('pagelist', function (
    ?string $primary,
    ?array $secondary,
    string $path
): string {
    global $wiki;

    $dir = dirname($path);
    $snippet = '';
    $cols = [];

    if ($secondary['cols']) {
        $cols = explode(',', $secondary['cols']);
    }

    $snippet .= '|' . $primary . ' |';
    foreach ($cols as $col) {
        $snippet .= $col . ' |';
    }
    $snippet .= PHP_EOL;
    $snippet .= '|---|';
    foreach ($cols as $col) {
        $snippet .= '---|';
    }
    $snippet .= PHP_EOL;

    $pattern = '/^' . str_replace('*', '.*', $secondary['match']) . '$/';
    foreach (scandir($dir) as $filename) {
        if (preg_match($pattern, $filename)) {
            $absFilename = $dir . '/' . $filename;
            list($metadata, $content) = $wiki->loadFile($absFilename, true);
            if ($primary === '#grouplist') {
                $snippet .= '[' . $metadata['title'] . '](' .
                    $wiki->findURLPathForContentFile($absFilename) . ')  ' . PHP_EOL;
            } else {
                $snippet .= '|[' . $metadata['title'] . '](' .
                    $wiki->findURLPathForContentFile($absFilename) . ')|';
                foreach ($cols as $col) {
                    preg_match_all('/^' . $col . ':\s+(.*)/m', $content, $field);
                    $snippet .= $field[1][0] . ' |';
                }
                $snippet .= PHP_EOL;
            }
        }
    }

    return $snippet;
});

$wiki->registerMacro('paginate', function (
    ?string $primary,
    ?array $secondary,
    string $path
): string {
    $snippet = '';
    $pages = [];
    $myIndex = -1;
    $basename = basename($path);

    // load all matching files
    $pattern = '/^' . str_replace('*', '.*', $primary) . '$/';
    foreach (scandir(dirname($path)) as $filename) {
        if (preg_match($pattern, $filename)) {
            if (is_file(dirname($path) . '/' . $filename)) {
                $pages[] = preg_replace('/\.md$/', '', $filename);
                if (basename($filename) === $basename) { // hey - it's us!
                    $myIndex = sizeof($pages) - 1;
                }
            }
        }
    }

    // output pagination
    if ($myIndex < 0) {
        return '{{error prevnext-not-found}}';
    }
    if ($myIndex > 0) {
        $snippet .= '[←](' . ($pages[$myIndex - 1]) . ') | ';
    }
    $snippet .= ___('Page %d of %d', $myIndex + 1, count($pages));
    if ($myIndex < sizeof($pages) - 1) {
        $snippet .= ' | [→](' . ($pages[$myIndex + 1]) . ')';
    }
    return $snippet;
});

// --- HTML snippets -----------------------------------------------------------

function getPageLinksHTML(
    $user,
    $wiki
): string {
    $html = '';
    if ($user->mayUpdate($wiki->getWikiPath())) {
        if ($wiki->exists()) {
            $html .= '<a href="?action=edit"><i class="fas fa-edit"></i> ' . ___('Edit') . '</a><br>';
        } else {
            $html .= '<a href="?action=createPage"><i class="fas fa-edit"></i> ' . ___('Create') . '</a><br>';
        }
    }
    if ($wiki->exists() && $user->mayRead($wiki->getWikiPath()) && $user->mayUpdate($wiki->getWikiPath())) {
        $html .= '<a href="?action=history"><i class="fas fa-history"></i> ' . ___('History') . '</a><br>';
    }
    if ($wiki->exists() && $user->mayDelete($wiki->getWikiPath())) {
        $html .= '<a href="?action=delete"><i class="fas fa-trash"></i> ' . ___('Delete') . '</a><br>';
    }
    if ($user->isLoggedIn()) {
        $html .= '<p>' . $_SESSION['username'] . '</p>';
        $html .= '<a href="?auth=logout"><i class="fas fa-sign-out-alt"></i> ' . ___('Logout') . '</a>';
    } else {
        $html .= '<a href="?auth=login"><i class="fas fa-sign-in-alt"></i> ' . ___('Login') . '</a>';
    }
    return $html;
}

/**
 * Convert a wiki path into a series of CSS classes.
 *
 * This is usefull to style pages depending on their folder or name. E.g.
 * `/animal/lion` -> `page page-animal page-animal-lion`
 *
 * @param string Wiki path to convert.
 * @return string Class string to be added to class="".
 */
function pathToClasses(
    string $wikiPath
): string {
    $css = '';
    $prefix = 'page';
    foreach (explode('/', $wikiPath) as $element) {
        if ($element === '') {
            $css = $prefix;
        } else {
            $css .= ' ' . $prefix . '-' . $element;
            $prefix = $prefix . '-' . $element;
        }
    }
    return trim($css);
}

/**
 * Beautify a udiff as html.
 *
 * @param string $diff The diff's content
 * @return string HTML markup for the diff.
 */
function diff2html(
    string $diff
): string {
    $html = '';
    $chunk = '';
    foreach (explode("\n", $diff) as $line) {
        switch ($line[0]) {
            case '@':
                $html .= $chunk . "\n<span class=\"info\">" . $line . "</span>\n";
                $chunk = '';
                break;
            case '-':
                $chunk = $chunk . '<span class="removed">' . substr($line, 1) . "</span>\n";
                break;
            case '+':
                $chunk = $chunk . '<span class="added">' . substr($line, 1) . "</span>\n";
                break;
                // $chunk = '<code class="added">' . $line . "</code>\n" . $chunk;
                // break;
        }
    }
    $html .= $chunk;
    return '<pre><code>' . substr($html, 1) . '</code></pre>';
}

/**
 * Generate the HTML header and open the <body>.
 *
 * @param at\nerdreich\Wiki $wiki Current CMS object.
 * @param array $config Wiki configuration.
 */
function outputHeader(array $config, string $path, string $title, string $description = '')
{
    ?><!doctype html>
<html class="no-js" lang="">
<head>
  <meta charset="utf-8">
  <title><?php echo htmlspecialchars($title); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="manifest" href="<?php echo $config['themePath']; ?>site.webmanifest">
  <link rel="apple-touch-icon" href="<?php echo $config['themePath']; ?>icon.png">
  <link rel="icon" href="<?php echo $config['themePath']; ?>favicon.ico"  type="image/x-icon">
  <style>body { background-color: $BGCOLOR$; color: rgba(255, 255, 255, 0.8); }</style>
  <link rel="stylesheet" href="<?php echo $config['themePath']; ?>style.css?v=$VERSION$">
</head>
<body class="<?php echo htmlspecialchars(pathToClasses($path)); ?>">
    <?php
}

/**
 * Generate the (top) navbar.
 *
 * @param at\nerdreich\Wiki $wiki Current CMS object.
 * @param at\nerdreich\UserSession $user Current user/Session object.
 */
function outputNavbar(at\nerdreich\Wiki $wiki, at\nerdreich\UserSession $user)
{
    ?>
<section class="section-has-bg navbar">
  <nav class="container">
    <div class="row">
      <div class="col-12">
        <?php echo beautify($wiki->getSnippetHTML('topnav')); ?>
        <div>
          <input id="wiki-burger" type="checkbox">
          <label for="wiki-burger"><i class="fas fa-cog"></i></label>
          <div class="wiki-menu">
            <?php echo getPageLinksHTML($user, $wiki); ?>
          </div>
        </div>
      </div>
    </div>
  </nav>
</section>
    <?php
}

/**
 * Generate the banner area.
 *
 * @param at\nerdreich\Wiki $wiki Current CMS object.
 */
function outputBanner(at\nerdreich\Wiki $wiki)
{
    ?>
<section class="section-has-bg banner">
  <nav class="container">
    <div class="row">
      <div class="col-12">
        <?php echo beautify($wiki->getSnippetHTML('banner')); ?>
      </div>
    </div>
  </nav>
</section>
    <?php
}

/**
 * Generate the footer and close <body> & <html>.
 *
 * @param at\nerdreich\Wiki $wiki Current CMS object.
 */
function outputFooter(at\nerdreich\Wiki $wiki)
{
    ?>
<footer class="container">
  <div class="row">
    <div class="col-12">
      <p>
        <a class="no-icon" href="<?php echo $wiki->getRepo(); ?>">wiki.md v<?php echo $wiki->getVersion(); ?></a>
        - <a class="no-icon" href="$URL$">Sunset Theme v$VERSION$</a>
        <?php if ($wiki->getDate() !== null) {
            echo '- ' . htmlspecialchars(___('Last saved %s', $wiki->getDate()->format($config['datetime'])));
        } ?>
        - <a href="/<?php __('Privacy'); ?>"><?php __('Privacy'); ?></a>
      </p>
    </div>
  </div>
</footer>
</body>
</html>
    <?php
}

// --- other helpers -----------------------------------------------------------

/**
 * Do various gentle content improvements, like icons ...
 *
 * @param string $html HTML to improve.
 * @return string Improved HTML.
 */
function beautify(
    string $html
): string {
    // fontawesome instead of emoji
    $html = str_replace('👤', '<i class="fas fa-user"></i>', $html);
    $html = str_replace('👥', '<i class="fas fa-users"></i>', $html);
    $html = str_replace('🎬', '<i class="fas fa-film"></i>', $html);
    $html = str_replace('📖', '<i class="fas fa-theater-masks"></i>', $html);
    $html = str_replace('♖', '<i class="fas fa-chess-knight"></i>', $html);
    $html = str_replace('📅', '<i class="far fa-calendar-alt"></i>', $html);
    $html = str_replace('🗄️', '<i class="fas fa-archive"></i>', $html);
    $html = str_replace('💬', '<i class="fas fa-theater-masks"></i>', $html);
    $html = str_replace('🏠', '<i class="fas fa-home"></i>', $html);
    $html = str_replace('🗺', '<i class="fas fa-map-marked-alt"></i>', $html);
    $html = str_replace('🪶', '<i class="fas fa-feather-alt"></i>', $html);

    return $html;
}
