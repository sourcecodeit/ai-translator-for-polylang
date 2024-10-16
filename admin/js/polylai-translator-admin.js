(function ($) {
	function checkProgress() {
		$.post(ajaxurl, {
			action: 'polylai_progress',
		}, function (ids) {
			$("#polylai-progress-panel").hide()
			if (ids.length) { 
				const post = ids.find(item => !!item.perc.locale)
				if (post) { 
					const localeIndex = post.locales.locales.split(',').indexOf(post.perc.locale)
					const locale = post.locales.locales_names.split(',')[localeIndex]
					$("#polylai-progress-panel [data-title]").html(post.title)
					$("#polylai-progress-panel [data-locales]").html(post.locales.locales_names)
					$("#polylai-progress-panel [data-perc]").html(locale + ": " + post.perc.perc + "%")
					$("#polylai-progress-panel").show()
				}				
			}
			$("[data-progress]").each(function () {
				const id = $(this).data("progress")
				const inProgress = ids.find(item => item.post == id)
				if (!inProgress) {
					$(this).hide()
					const prev = $(`#post-${id} .polylaitr-progress-locales`).text()
					$(`#post-${id} .polylaitr-progress-locales`).text('')
					if (prev) {
						$(`#post-${id} .polylaitr-progress-locales`).text("Completed! Refresh to see the translation.")
					}
				}
			})
			ids.forEach(item => {
				console.log(item)
				$(`#post-${item.post} .polylaitr-progress`).attr('data-progress', item.post).show()
				const locales = item.locales.locales.split(',')
				const locales_names = item.locales.locales_names.split(',')

				const text = locales_names.map((locale, i) => {
					const code = locales[i]
					const perc = item.perc?.locale == code ? item.perc?.perc + '%' : ''

					return `${locale} ${perc}`
				}).join(', ')

				$(`#post-${item.post} .polylaitr-progress-locales`).text(text)
			})			
		})
	}

	checkProgress()
	setInterval(checkProgress, 5000)

	function showTranslateModal(id) {
		$("#polylaitr-modal").remove()
		
		const modal = `
			<div id='polylaitr-modal'>
				<div class='polylaitr-panel'>
					<span data-close class="polylaitr-close dashicons dashicons-no"></span>
					<h2>PolylAI Translator</h2>

					<div id='polylaitr-content'>
						<p>Translate from <strong></strong></p>
						<ul id='polylaitr-langs'>
						</ul>

						<div class='polylaitr-ui-cron-msg polylai-warn polylai-hidden'>
							Cron is not running. You can run the translations manually but it's recommended to set up the cron.<br />
							Please check the <a href='/wp-admin/admin.php?page=polylai-translator-options'>PolylAI Translator Options</a> page
							for more information.
						</div>
						<br /><br />
						<div class='polylaitr-buttons'>
							<span data-close class='button'>Cancel</span>
							<span class='button' data-submit>Translate</span>
						</div>
					</div>					
				</div>
			</div>`
		
		$("body").append(modal)

		$("#polylaitr-modal [data-close]").click(function () {
			$("#polylaitr-modal").remove()
		})

		$("#polylaitr-modal [data-submit]").click(function () {
			const locales = []
			const localesNames = []
			$("#polylaitr-langs input[data-polylai-active]:checked").each(function () {
				locales.push(this.value)
				localesNames.push($(this).data("name"))
			})
			const nonce = $(this).data("nonce")
			if (locales.length) {
				$.post(ajaxurl, {
					id,
					locales: locales.join(','),
					localesNames: localesNames.join(','),
					action: 'polylai_enqueue_translations',
					nonce
				}, function (response) {
					$("#polylaitr-content").html("Translations enqueued!")
					checkProgress()
				})	
			}			
		})

		function checkDisabled() {
			if ($("#polylaitr-langs input:checked").length) {
				$("#polylaitr-modal [data-submit]").removeClass('disabled')
			} else {
				$("#polylaitr-modal [data-submit]").addClass('disabled')
			}
		}


		$.post(ajaxurl, {
			id,
			action: 'polylai_get_post_tr_status'
		}, function (response) {			
			$("#polylaitr-content strong").text(response.post_lang)	

			$("#polylaitr-modal [data-submit]").data("nonce", response.nonce)

			Object.keys(response.locales).forEach(code => {
				if (code == response.post) {
					return
				}
				// const running = response.running?.locales?.split(',') || []
				let running = []
				response.running.forEach(item => {
					if (item.data) {
						running = [...running, ...item.data.locales.split(',')]
					}
				})
				const locale = response.locales[code]
				const hasTranslation = response.translations[code] || false
				const isRunning = running.includes(code)
				const isAllowed = response.allowed.includes(code)

				let icon = 'minus'
				if (hasTranslation) {
					icon = 'yes'
				}
				if (isRunning) {
					icon = 'update'
				}
			
				const isDisabled = hasTranslation || isRunning || !isAllowed

				$("#polylaitr-langs").append(`
					<li>
						<label>
							<input ${isDisabled ? 'disabled' : '' } ${hasTranslation || isRunning ? 'checked' : 'data-polylai-active'} type='checkbox' data-name='${locale.name}' value='${code}' />
							${locale.name}

							<span class="dashicons dashicons-${icon} ${isRunning ? 'polylaitr-rotate' : ''}"></span>
						</label>
					</li>
				`)				
			})
			checkDisabled()
			if (!response.has_cron) {
				$("#polylaitr-modal .polylaitr-ui-cron-msg").removeClass('polylai-hidden')
			}
			$('#polylaitr-modal input[type="checkbox"]').change(checkDisabled)
		});
	}
	
	$("body").ready(function () {
		$(".polylaitr-link").click(function () {
			const id = $(this).data("id")
			showTranslateModal(id)
		})

		if ($("#polylai_translator_options_openai_temp").length) {
			const tempVal = $("#polylai_translator_options_openai_temp_val")
			$("#polylai_translator_options_openai_temp").get(0).oninput = function () {
				tempVal.text(this.value)
			}
		} 	
		
		$("#polylai-ai-engine").change(function () {
			const engine = $(this).val()
			const pro = $(this).data("pro")
			if (engine == 'claude' && !pro) {
				$("#polylai-ai-engine-claude").removeClass('polylai-hidden')
				$(this).val('openai')
			} else {
				$("#polylai-ai-engine-claude").addClass('polylai-hidden')
			}
		})

		$("#polylai-run-cron").click(function () {
			const nonce = $(this).data("nonce")
			if (!nonce) {
				return
			}
			$.post(ajaxurl, {
				action: 'polylai_run_cron',
				nonce: nonce
			}, function (response) {
				console.log(response)
			})
		})

		$("[data-polyalai-close]").click(function () {			
			$(".polylai-modal-bg").hide()
		})

		$("[data-polylai-open-modal]").click(function () {
			const id = $(this).data("polylai-open-modal")
			$("#" + id).show()

			if (id == 'polylai-modal-logs') {
				$("#polylai-logs tbody").empty().append('<tr><td colspan="5">Loading...</td></tr>')
				$.post(ajaxurl, {
					id,
					action: 'polylai_logs'
				}, function (response) { 
					console.log(response)

					$("#polylai-logs tbody").empty()

					response.forEach(item => {
						$("#polylai-logs tbody").append(`
							<tr>
								<td>${item.time}</td>
								<td>
									<span class="polylai-log-badge log-type-${item.type}">${item.type}</span>
								</td>
								<td>${item.operation}</td>
								<td>${item.message ? item.message : ''}</td>
								<td>${item.post_id ? item.post_id : ''}</td>
								<td>${item.post_title ? item.post_title : ''}</td>								
							</tr>
						`)
					})
				})
			}
		})

		$("[data-polylai-engine]").change(function () {
			const values = ['openai', 'claude']
			const engine = $(this).val()
			const $form = $(".polylai-form")
			$(".form-table", $form).eq(1).hide()
			$(".form-table", $form).eq(2).hide()
			$("h3", $form).eq(1).hide()
			$("h3", $form).eq(2).hide()
			$(".form-table", $form).eq(values.indexOf(engine) + 1).show()
			$("h3", $form).eq(values.indexOf(engine) + 1).show()
		}).change()

		$("[data-download-logs]").click(function () { 

			// make the ajax call and download the file

			$.post(ajaxurl, {
				action: 'polylai_logs_download'
			}, function (response) {

				const blob = new Blob([response], { type: 'text/json' });
				const downloadUrl = URL.createObjectURL(blob);
				const a = document.createElement("a");
				a.href = downloadUrl;
				a.download = "polylai-logs.txt";
				document.body.appendChild(a);
				a.click();
			})
		})
	})	

})( jQuery );
