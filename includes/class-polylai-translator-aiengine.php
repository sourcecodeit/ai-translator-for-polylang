<?php if (!defined('ABSPATH'))
	exit; ?>
<?php

interface polylai_AIEngine {
	public function translate($text, $locale_from, $locale_to, $post_id);
}