<?php

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

outputHeader($wiki, ___('Login'));
outputNavbar($wiki);
outputBanner($wiki);

?>
<section class="section-main container">
  <div class="row">
    <div class="col-12">
      <h2><?php __('Login required'); ?></h2>
      <form action="?<?php echo $wiki->getActions(); ?>&amp;auth=login" method="post">
        <?php if (!$wiki->user->isLoginSimple()) { ?>
        <label for="username" class="in-border"><?php __('Username'); ?></label>
        <input id="username" type="text" name="username" required autofocus>
        <?php } ?>
        <label for="password" class="in-border"><?php __('Password'); ?></label>
        <input id="password" type="password" name="password" required<?php
            echo $wiki->user->isLoginSimple() ? ' autofocus' : '';
        ?>>
        <input type="submit" class="primary" value="<?php __('Login'); ?>">
      </form>
    </div>
  </div>
</section>
<?php outputFooter($wiki); ?>
