<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class polylai_Settings {
    public function register_settings() {
        add_option( 'polylai_translator_options', [
            'ai_engine'    => 'openai',
            'openai_key'   => '',
            'openai_org'   => '',
            'openai_temp'  => '0',
            'openai_model' => 'gpt-4o',
            'claude_key'   => '',
            'claude_model' => 'claude-3-sonnet-20240229',
        ] );
        register_setting( 'polylai_translator_options', 'polylai_translator_options', [
            "type"              => "array",
            "sanitize_callback" => "polylai_Utils::sanitize_options",
        ] );
        add_settings_section(
            'general_settings',
            '',
            [$this, 'general_settings'],
            'polylai_translator'
        );
        add_settings_section(
            'openai_settings',
            '',
            [$this, 'openai_settings'],
            'polylai_translator'
        );
        add_settings_section(
            'claude_settings',
            '',
            [$this, 'claude_settings'],
            'polylai_translator'
        );
        add_settings_field(
            'polylai_ai_engine',
            'AI Engine',
            [$this, 'polylai_ai_engine'],
            'polylai_translator',
            'general_settings'
        );
        add_settings_field(
            'polylai_openai_key',
            'OpenAI API Key',
            [$this, 'polylai_openai_key'],
            'polylai_translator',
            'openai_settings'
        );
        add_settings_field(
            'polylai_openai_org',
            'OpenAI Organization ID',
            [$this, 'polylai_openai_org'],
            'polylai_translator',
            'openai_settings'
        );
        add_settings_field(
            'polylai_openai_model',
            'OpenAI Model',
            [$this, 'polylai_openai_model'],
            'polylai_translator',
            'openai_settings'
        );
        add_settings_field(
            'polylai_langs',
            'Languages',
            [$this, 'polylai_langs'],
            'polylai_translator',
            'general_settings'
        );
    }

    public function openai_settings() {
        ?>
		<h3>OpenAI Settings</h3>
		<?php 
    }

    public function claude_settings() {
        ?>
		<h3>Claude Settings</h3>
		<?php 
    }

    public function polylai_ai_engine() {
        $options = get_option( 'polylai_translator_options' );
        if ( !isset( $options['ai_engine'] ) ) {
            $options['ai_engine'] = 'openai';
        }
        $items = [
            "openai" => "OpenAI",
            "claude" => "Claude",
        ];
        ?>
							<select data-polylai-engine data-pro="<?php 
        ?>" name='polylai_translator_options[ai_engine]' id="polylai-ai-engine">
								<?php 
        foreach ( $items as $k => $item ) {
            ?>
												<option <?php 
            echo ( $k == $options['ai_engine'] ? 'selected' : '' );
            ?> value="<?php 
            echo esc_attr( $k );
            ?>"><?php 
            echo esc_html( $item );
            ?></option>
								<?php 
        }
        ?>
							</select>
							<?php 
        if ( !polylai_fs()->can_use_premium_code() ) {
            ?>
									<div class="polylai-hidden" id="polylai-ai-engine-claude">
										<div class="polylai-warn">
											Claude is not available on free plan, 
											<a href="<?php 
            echo esc_html( polylai_fs()->get_upgrade_url() );
            ?>">upgrade to use it</a>.
										</div>
									</div>
							<?php 
        }
        ?>
							<?php 
    }

    public function polylai_langs() {
        $langs = [];
        if ( function_exists( 'pll_the_languages' ) ) {
            $langs = pll_the_languages( [
                'raw'           => 1,
                'hide_if_empty' => 0,
            ] );
            $default_lang = pll_default_language( 'slug' );
            $default_lang_name = pll_default_language( 'name' );
            $allowed = polylai_Utils::allowed_langs();
            ?>
			<span class="polylai-lang-badge"><?php 
            echo esc_html( $default_lang_name );
            ?></span>
			<?php 
            foreach ( $langs as $lang ) {
                if ( $lang['slug'] == $default_lang ) {
                    continue;
                }
                $disabled = ( in_array( $lang["slug"], $allowed ) ? '' : 'polylai-disabled' );
                ?>
					<span class="polylai-lang-badge <?php 
                echo esc_attr( $disabled );
                ?>"><?php 
                echo esc_html( $lang["name"] );
                ?></span>
					<?php 
            }
        } else {
            ?>
			<div>
				<div class="polylai-warn">
					Polylang plugin not installed or not activated.
				</div>
			</div>
			<?php 
        }
        ?>		
			<?php 
        if ( !polylai_fs()->can_use_premium_code() && count( $langs ) > 1 ) {
            ?>
					<div>
						<div class="polylai-warn">
							Free version only supports one language other than the default one,
							<a href="<?php 
            echo esc_url( polylai_fs()->get_upgrade_url() );
            ?>">upgrade to unlock</a> more.
						</div>
					</div>
			<?php 
        }
        ?>
			<?php 
    }

    public function polylai_openai_key() {
        $options = get_option( 'polylai_translator_options' );
        ?>
						<fieldset data-group="openai">
							<input
								class='regular-text'
								name='polylai_translator_options[openai_key]' 
								type='text' value='<?php 
        echo esc_attr( $options['openai_key'] );
        ?>' />

							<p class="description">
								Your OpenAI API key (find it 
								<a target="_blank" href="https://platform.openai.com/account/api-keys">here</a>)
							</p>
						</fieldset>
						<?php 
    }

    public function claude_key() {
        $options = get_option( 'polylai_translator_options' );
        if ( !isset( $options['claude_key'] ) ) {
            $options['polylai_ai_claude_key'] = '';
        }
        ?>
							<fieldset data-group="claude">
								<input
									class='regular-text'
									name='polylai_translator_options[claude_key]' 
									type='text' value='<?php 
        echo esc_attr( $options['claude_key'] );
        ?>' />

								<p class="description">
									Your Claude API key (find it 
									<a target="_blank" href="https://console.anthropic.com/settings/keys">here</a>)
								</p>
							</fieldset>
							<?php 
    }

    public function claude_model() {
        $options = get_option( 'polylai_translator_options' );
        if ( !isset( $options['claude_model'] ) ) {
            $options['claude_model'] = 'claude-3-sonnet-20240229';
        }
        $items = [
            "claude-3-5-sonnet-20240620" => "Claude 3.5 Sonnet",
            "claude-3-sonnet-20240229"   => "Claude 3 Sonnet",
            "claude-3-haiku-20240307"    => "Claude 3 Haiku",
        ];
        ?>
		<fieldset data-group="claude">
			<select  name='polylai_translator_options[claude_model]'>
				<?php 
        foreach ( $items as $k => $item ) {
            ?>
								<option <?php 
            echo ( $k == $options['claude_model'] ? 'selected' : '' );
            ?> value="<?php 
            echo esc_attr( $k );
            ?>"><?php 
            echo esc_html( $item );
            ?></option>
				<?php 
        }
        ?>
			</select>
		</fieldset>
		<?php 
    }

    public function polylai_openai_org() {
        $options = get_option( 'polylai_translator_options' );
        ?>
						<fieldset data-group="openai">
							<input
								class='regular-text'
								name='polylai_translator_options[openai_org]' 
								type='text' value='<?php 
        echo esc_attr( $options['openai_org'] );
        ?>' />

							<p class="description">
								Your OpenAI Organization ID (find it 
								<a target="_blank" href="https://platform.openai.com/account/org-settings">here</a>)
							</p>
						</fieldset>
						<?php 
    }

    public function polylai_cron_key() {
        $options = get_option( 'polylai_translator_options' );
        if ( !isset( $options['cron_key'] ) ) {
            $options['cron_key'] = '<enter random string>';
        }
        ?>
								<fieldset>
									<input
										class='regular-text'
										name='polylai_translator_options[cron_key]' 
										type='text' value='<?php 
        echo esc_attr( $options['cron_key'] );
        ?>' />

									<p class="description">
										Enter a random and secure string here.
									</p>
								</fieldset>
								<?php 
    }

    public function polylai_openai_temp() {
        $options = get_option( 'polylai_translator_options' );
        ?>
						<fieldset>
							<span id="polylai_translator_options_openai_temp_val"><?php 
        echo esc_attr( $options['openai_temp'] );
        ?></span>
							<input
								id='polylai_translator_options_openai_temp' 
								name='polylai_translator_options[openai_temp]' 
								type='range' min="0" max="100" 
								value='<?php 
        echo esc_attr( $options['openai_temp'] );
        ?>' />

							<p class="description">
								What sampling temperature to use, between 0 and 200. 
								Higher values like 80 will make the output more random, 
								while lower values like 20 will make it more focused and deterministic.
							</p>
						</fieldset>
						<?php 
    }

    public function polylai_openai_model() {
        $options = get_option( 'polylai_translator_options' );
        $items = [
            "gpt-4o-mini"       => "GPT 4o mini (recommended)",
            "gpt-4o"            => "GPT 4o",
            "gpt-4-turbo"       => "GPT 4 Turbo",
            "gpt-4"             => "GPT 4",
            "gpt-4-32k"         => "GPT 4 32k",
            "gpt-3.5-turbo"     => "GPT 3.5 Turbo",
            "gpt-3.5-turbo-16k" => "GPT 3.5 Turbo 16k",
        ];
        ?>
		<fieldset data-group="openai">
						<select  name='polylai_translator_options[openai_model]'>
							<?php 
        foreach ( $items as $k => $item ) {
            ?>
										<option <?php 
            echo ( $k == $options['openai_model'] ? 'selected' : '' );
            ?> value="<?php 
            echo esc_attr( $k );
            ?>"><?php 
            echo esc_html( $item );
            ?></option>
							<?php 
        }
        ?>
						</select>
						</fieldset>
						<?php 
    }

    public function general_settings() {
        ?>
		
		<div class="polylai-section-alerts">


		<div class="polylai-alert-panel polylai-info polylai-side" style="display: none;" id="polylai-progress-panel">
			<h4>Translation progress</h4>
			<p><strong>Translating:</strong> <span data-title></span>: 			
				<span data-locales></span>
				(<span data-perc></span>)
			</p>

		</div>
		
		<?php 
        $running = get_transient( polylai_Cron::ACTIVITY_TRANSIENT_KEY );
        ?>
		
			<?php 
        if ( !$running ) {
            ?>
			<div class="polylai-alert-panel">
				<h4>Cron is not running!</h4>
				<p>It is <strong>highly recommended</strong> to set up the cron for content translation with WP PolylAI. Without the use of cron the plugin may not work correctly.
					<br/>
					<br />
					<a href="#" data-polylai-open-modal="polylai-modal-cron">
						<strong>Show me how to set up the cron</strong>	
					</a>
				</p>
			</div>
			<?php 
        }
        ?>

			<?php 
        if ( is_plugin_active( 'elementor/elementor.php' ) ) {
            ?>
			<div class="polylai-alert-panel">
				<h4>Elementor detected</h4>
				<p>PolylAI Translator <strong>cannot translate</strong> posts and pages created with Elementor. </p>
			</div>
			<?php 
        }
        ?>

			<?php 
        if ( !is_plugin_active( 'polylang/polylang.php' ) ) {
            ?>
				<div class="polylai-alert-panel">
					<h4>Polylang not installed or not activated</h4>
					<p>PolylAI Translator <strong>cannot translate</strong> posts and pages created with Elementor. </p>
				</div>
			<?php 
        }
        ?>
		</div>

		<?php 
        $running = get_transient( polylai_Cron::ACTIVITY_TRANSIENT_KEY );
        if ( $running ) {
            echo "<div class='polylai-cron-activity'>Cron last activity: " . esc_html( $running ) . "</div>";
        }
        ?>

		<div style="margin-top: 20px;">
			<a class="button" data-polylai-open-modal="polylai-modal-logs">Show logs</a>
		</div>

		<h3>General Settings</h3>
		<?php 
    }

}
