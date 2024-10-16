<?php if (!defined('ABSPATH'))
	exit; ?>
<?php

class polylai_ChatGPT implements polylai_AIEngine
{

	private function makeCall($endpoint, $data = null)
	{
		$options = get_option('polylai_translator_options');

		$api_key = $options['openai_key'];
		$org_id = $options['openai_org'];

		$response = wp_remote_post("https://api.openai.com/v1$endpoint", [
			'timeout' => 100,
			'method' => 'POST',
			'blocking' => true,
			'body' => json_encode($data), // cannot use wp_json_encode here
			'headers' => [
				"Authorization" => "Bearer $api_key",
				"Content-Type" => "application/json",
				"OpenAI-Organization" => $org_id
			]
		]);

		// print_r($response['body']);

		if (is_wp_error($response)) {
			polylai_Utils::db_log('error', 'translate', $response->get_error_message());
			throw new Exception(esc_html($response->get_error_message()));
		} else {
			return json_decode($response['body']);
		}
	}

	public function translate($text, $locale_from, $locale_to, $post_id)
	{
		polylai_Utils::db_log('debug', 'chatgpt', "translate ($locale_to): $text");
		$options = get_option('polylai_translator_options');
		$prompt = "translate from $locale_from to $locale_to the following HTML. 
			Do not alter the HTML structure, even if it is not valid HTML.
			Do not add any HTML tags, only translate the text.
			Do not try to open or close HTML tags.
			Do not attempt to translate the script tags and do not alter them.
			Do not answer \"Sure\", you have to only translate: \n\n[text]";
		$prompt = str_replace('[text]', $text, $prompt);

		$data = array();
		$data["model"] = $options['openai_model'];
		$data["messages"] = [
			["role" => "system", "content" => "You are a professional translator."],
			["role" => "user", "content" => $prompt]
		];
		$data["temperature"] = 0;

		try {
			polylai_Utils::db_log('debug', 'chatgpt', "before call");
			$result = $this->makeCall('/chat/completions', $data);
			if (isset($result->error)) {
				polylai_Utils::db_log('debug', 'chatgpt', "error");
				throw new Exception($result->error->message);
			}
			polylai_Utils::db_log('debug', 'chatgpt', "after call");
		} catch (Exception $e) {
			polylai_Utils::db_log('error', 'translate', 'ChatGPT response error: ' . $e->getMessage(), $post_id, null, $text);
			return $text;
		}
		return $result->choices[0]->message->content;
	}

}