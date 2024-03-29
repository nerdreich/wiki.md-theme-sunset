<?php // phpcs:ignore

/**
 * Copyright 2020-2024 Markus Leupold-Löwenthal
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

// load history
$history = $wiki->core->getHistory();

outputHeader($wiki, ___('History') . ': ' . $wiki->core->getTitle());
outputNavbar($wiki);
outputBanner($wiki);

function historyDate($date): string
{
    global $wiki;
    if ($date === null) {
        return ___('unknown');
    }
    if (is_string($date)) { // transparently convert string date (from history)
        $date = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $date);
    }
    return $date->format($wiki->getDateTimeFormat());
}

$restoreEnabled = true;

?><section class="section-main container page-history">
  <div class="row">
    <div class="col-12 col-md-8 col-lg-9">
      <h2><?php __('History for %s', $wiki->core->getWikiPath()); ?></h2>
      <?php if ($wiki->core->isDirty()) { ?>
        <p><?php __('The checksum of this page is invalid. Save the page in wiki.md again to correct this.') ?>
      <?php } ?>

      <dl class="timeline">
        <?php if ($history === null) { ?>
          <dt id="history-0">
            <p>
              <?php __('This page has not been saved by wiki.md.'); ?>
              <?php __('No history is available.'); ?>
            </p>
          </dt>
        <?php } else { ?>
            <?php $version = count($history) + 1; ?>
            <dt id="history-<?php echo $version; ?>">
              <h2 class="h4"><?php __('Version'); ?> <?php echo $version--; ?> (<?php __('current'); ?>)</h2>
              <p>
                <span class="minor"><?php
                    __('by %s at %s', $wiki->core->getAuthor(), historyDate($wiki->core->getDate()));
                ?></span>
              </p>
            </dt>
        <?php } ?>
        <?php foreach (array_reverse($wiki->core->getHistory() ?? []) as $change) { ?>
          <dd><?php if (($change['diff'] ?? null) !== null) {
                    echo diff2html(gzuncompress(base64_decode($change['diff'])));
              } else {
                    echo ___('No record available.');
                    $restoreEnabled = false;
              } ?></dd>
          <dt id="history-<?php echo $version; ?>">
            <h2 class="h4"><?php __('Version'); ?> <?php echo $version; ?></h2>
            <p>
              <span class="minor"><?php __('by %s at %s', $change['author'], historyDate($change['date'])); ?></span>
              <?php if ($restoreEnabled && !$wiki->core->isDirty()) { ?>
                - <a href="?page=restore&version=<?php echo $version; ?>"><?php __('restore'); ?></a>
              <?php } ?>
            </p>
          </dt>
            <?php $version--;
        } ?>
      </dl>
    </div>
    <nav class="col-12 col-md-4 col-lg-3 sidenav">
      <?php echo $wiki->core->getSnippetHTML('nav'); ?>
    </nav>
  </div>
</section>
<?php outputFooter($wiki); ?>
