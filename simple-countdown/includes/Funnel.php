<?php
/**
 * Contextual upgrade prompts, driven by what is true about this site.
 *
 * This replaces the settings-page sidebar listing every product we sell. That
 * sidebar asks a person to identify their own need from twenty-five options at
 * the one moment they have no need, which is why 98% of installs never reach
 * the store.
 *
 * The offers here are decided by a condition callback that inspects the site
 * and returns real numbers, rather than by a usage counter. Counters sound
 * reasonable and mostly never fire: a plugin whose job is passive enablement
 * can run for a year without the user "doing" anything countable. What is
 * always available is the state of their own library, and telling someone a
 * true and specific thing about their site is a better offer than an advert.
 *
 * Deliberately self-contained: no dependency on Core, so it drops into any of
 * the free plugins whose copies of Core have drifted apart.
 *
 * @package GrandPlugins
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GPLS_Funnel' ) ) {

	/**
	 * Site-aware upgrade prompts.
	 */
	class GPLS_Funnel {

		const STORE   = 'https://grandplugins.com/product/';
		const VERSION = 1;

		/**
		 * Days of quiet after any dismissal, so offers never feel like
		 * whack-a-mole.
		 */
		const COOLDOWN = 30;

		/**
		 * Config for the plugin that booted this.
		 *
		 * @var array
		 */
		private static $config = array();

		/**
		 * Start the funnel for one plugin.
		 *
		 * @param array $config {
		 *     @type string $slug    Plugin slug; used in tagging and keys.
		 *     @type string $name    Human name.
		 *     @type array  $screens Admin screen ids the prompt may appear on.
		 *     @type string $cap     Capability required. Default 'upload_files'.
		 *     @type array  $offers  Ordered; the first eligible one is shown.
		 * }
		 * @return void
		 */
		public static function boot( array $config ) {
			if ( empty( $config['slug'] ) || empty( $config['offers'] ) ) {
				return;
			}

			// Never translate at boot time: see eligible_offer(). Anything the
			// component owns goes through self::text() at render instead.
			if ( ! is_array( $config['offers'] ) && ! is_callable( $config['offers'] ) ) {
				return;
			}

			self::$config = wp_parse_args(
				$config,
				array(
					'name'       => $config['slug'],
					'screens'    => array(),
					'cap'        => 'manage_options',
					'textdomain' => 'default',
				)
			);

			add_action( 'admin_notices', array( __CLASS__, 'render' ) );
			add_action( 'wp_ajax_gpls_funnel_dismiss', array( __CLASS__, 'dismiss' ) );
		}

		/**
		 * Translate one of the component's own strings.
		 *
		 * The domain comes from config, so this file drops into any plugin
		 * without being edited.
		 *
		 * @param string $text String to translate.
		 * @return string
		 */
		private static function text( $text ) {
			return esc_html( translate( $text, self::$config['textdomain'] ) );
		}

		/**
		 * How many images of the given types sit in the media library.
		 *
		 * Provided as a helper because most of our catalogue is image tooling
		 * and every one of those plugins wants this number.
		 *
		 * @param array $mimes Mime types to total up.
		 * @return int
		 */
		public static function library_count( array $mimes ) {
			$counts = (array) wp_count_attachments();
			$total  = 0;

			foreach ( $mimes as $mime ) {
				if ( isset( $counts[ $mime ] ) ) {
					$total += (int) $counts[ $mime ];
				}
			}

			return $total;
		}

		/**
		 * Show the first offer this site qualifies for.
		 *
		 * @return void
		 */
		public static function render() {
			if ( ! current_user_can( self::$config['cap'] ) || ! self::on_relevant_screen() ) {
				return;
			}

			if ( self::in_cooldown() ) {
				return;
			}

			$offer = self::eligible_offer();

			if ( ! $offer ) {
				return;
			}

			$url = self::link( $offer['product'], $offer['id'] );

			self::print_styles();

			printf(
				'<div class="notice is-dismissible gpls-funnel" data-gpls-offer="%s">
					<div class="gpls-funnel-in">
						%s
						<div class="gpls-funnel-copy">
							<p class="gpls-funnel-title">%s</p>
							<p class="gpls-funnel-body">%s</p>
							<p class="gpls-funnel-actions">
								<a class="gpls-funnel-cta" href="%s" target="_blank" rel="noopener">%s</a>
								<button type="button" class="gpls-funnel-hide">%s</button>
							</p>
						</div>
					</div>
				</div>',
				esc_attr( $offer['id'] ),
				self::stat_markup( $offer ),
				$offer['title'],
				$offer['body'],
				esc_url( $url ),
				$offer['cta'],
				self::text( 'No thanks' )
			);

			self::print_dismiss_script();
		}

		/**
		 * First offer whose condition holds and which is not dismissed.
		 *
		 * @return array|false
		 */
		private static function eligible_offer() {
			$user = get_current_user_id();

			// Offers may be given as a callable so that any __() inside them is
			// evaluated here - on admin_notices - rather than whenever the host
			// plugin happens to boot. Plugins here boot on plugins_loaded, and
			// translating that early triggers WordPress 6.7's
			// "translation loading was triggered too early" notice.
			$offers = self::$config['offers'];

			if ( ! is_array( $offers ) && is_callable( $offers ) ) {
				$offers = (array) call_user_func( $offers );
			}

			foreach ( (array) $offers as $key => $offer ) {
				$offer = wp_parse_args(
					$offer,
					array(
						'id'      => (string) $key,
						'product' => '',
						'when'    => null,
						'title'      => '',
						'body'       => '',
						'stat'       => '',
						'stat_label' => '',
						'cta'        => self::text( 'Take a look' ),
					)
				);

				if ( ! $offer['product'] || ! is_callable( $offer['when'] ) ) {
					continue;
				}

				$offer['id'] = self::$config['slug'] . ':' . $offer['id'];

				if ( get_user_meta( $user, self::dismiss_key( $offer['id'] ), true ) ) {
					continue;
				}

				$tokens = call_user_func( $offer['when'] );

				if ( empty( $tokens ) || ! is_array( $tokens ) ) {
					continue;
				}

				$offer['title']      = self::fill( $offer['title'], $tokens );
				$offer['body']       = self::fill( $offer['body'], $tokens );
				$offer['stat']       = self::fill( $offer['stat'], $tokens );
				$offer['stat_label'] = self::fill( $offer['stat_label'], $tokens );

				return $offer;
			}

			return false;
		}

		/**
		 * Replace {tokens} with the numbers the condition found.
		 *
		 * @param string $text   Copy containing {tokens}.
		 * @param array  $tokens Replacements.
		 * @return string
		 */
		private static function fill( $text, array $tokens ) {
			foreach ( $tokens as $k => $v ) {
				$text = str_replace(
					'{' . $k . '}',
					is_numeric( $v ) ? number_format_i18n( $v ) : (string) $v,
					$text
				);
			}

			return $text;
		}

		/**
		 * Remember that this user does not want this offer, and stay quiet for
		 * a while before making another.
		 *
		 * @return void
		 */
		public static function dismiss() {
			check_ajax_referer( 'gpls_funnel', 'nonce' );

			$offer = isset( $_POST['offer'] ) ? sanitize_text_field( wp_unslash( $_POST['offer'] ) ) : '';

			if ( $offer ) {
				$user = get_current_user_id();
				update_user_meta( $user, self::dismiss_key( $offer ), time() );
				update_user_meta( $user, self::quiet_key(), time() + ( self::COOLDOWN * DAY_IN_SECONDS ) );
			}

			wp_send_json_success();
		}

		/**
		 * Are we still being quiet after a recent dismissal?
		 *
		 * @return bool
		 */
		private static function in_cooldown() {
			$until = (int) get_user_meta( get_current_user_id(), self::quiet_key(), true );

			return $until > time();
		}

		/**
		 * Build the outbound link.
		 *
		 * Each placement gets its own medium so the next round of work can be
		 * decided from data. Previously every link in every plugin shared
		 * utm_medium=sidebar, which made all of them unmeasurable.
		 *
		 * @param string $product  Product slug on the store.
		 * @param string $offer_id Which offer produced this click.
		 * @return string
		 */
		private static function link( $product, $offer_id ) {
			$parts = explode( ':', $offer_id );

			return add_query_arg(
				array(
					'utm_source'   => 'free',
					'utm_medium'   => 'context',
					'utm_campaign' => 'funnel_v' . self::VERSION,
					'utm_content'  => self::$config['slug'],
					'utm_term'     => end( $parts ),
				),
				self::STORE . rawurlencode( $product ) . '/'
			);
		}

		/**
		 * Only speak up where the person is already thinking about this plugin.
		 *
		 * @return bool
		 */
		private static function on_relevant_screen() {
			if ( ! function_exists( 'get_current_screen' ) ) {
				return false;
			}

			$screen = get_current_screen();

			if ( ! $screen ) {
				return false;
			}

			foreach ( (array) self::$config['screens'] as $id ) {
				// Exact by default. Substring matching is too loose: 'media'
				// also matches 'options-media', which put this notice on
				// Settings > Media where it has no business being. A trailing
				// asterisk opts in to prefix matching for generated page hooks.
				if ( '*' === substr( $id, -1 ) ) {
					if ( 0 === strpos( $screen->id, rtrim( $id, '*' ) ) ) {
						return true;
					}
				} elseif ( $screen->id === $id ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * User-meta key for a dismissal.
		 *
		 * @param string $offer_id Offer identifier.
		 * @return string
		 */
		private static function dismiss_key( $offer_id ) {
			return 'gpls_funnel_off_' . md5( $offer_id );
		}

		/**
		 * User-meta key for the post-dismissal quiet period.
		 *
		 * @return string
		 */
		private static function quiet_key() {
			return 'gpls_funnel_quiet_' . md5( self::$config['slug'] );
		}

		/**
		 * The number, given the weight it deserves.
		 *
		 * The whole point of these offers is a true figure from the reader's
		 * own site. Buried mid-sentence it reads as marketing copy; set apart
		 * it reads as a measurement, which is what it is.
		 *
		 * Offer text arrives already escaped - every string is defined with
		 * esc_html__() - so it is printed as-is here. Escaping again would
		 * turn an apostrophe into &amp;#039; on screen.
		 *
		 * @param array $offer Resolved offer.
		 * @return string
		 */
		private static function stat_markup( $offer ) {
			if ( empty( $offer['stat'] ) ) {
				return '';
			}

			return sprintf(
				'<div class="gpls-funnel-stat"><span class="gpls-funnel-n">%s</span><span class="gpls-funnel-l">%s</span></div>',
				$offer['stat'],
				isset( $offer['stat_label'] ) ? $offer['stat_label'] : ''
			);
		}

		/**
		 * Scoped styles, printed once.
		 *
		 * Self-consistent rather than inheriting the admin colour scheme: a
		 * user running the pink or green scheme would otherwise get a clash
		 * between our accent and their buttons. Teal reads as efficiency and
		 * sits quietly against the grey admin background without looking like
		 * a warning or an advert.
		 *
		 * @return void
		 */
		private static function print_styles() {
			static $done = false;

			if ( $done ) {
				return;
			}

			$done = true;
			?>
			<style>
			.gpls-funnel{
				border:0;border-left:4px solid #0f766e;
				background:#f4fbfa;
				padding:0;
				box-shadow:0 1px 2px rgba(16,24,40,.06);
			}
			.gpls-funnel-in{display:flex;gap:18px;align-items:flex-start;padding:18px 38px 18px 20px;}
			.gpls-funnel-stat{
				flex:none;min-width:92px;
				background:#0f766e;color:#fff;
				border-radius:6px;padding:12px 14px;text-align:center;
			}
			.gpls-funnel-n{
				display:block;font-size:26px;line-height:1.05;font-weight:700;
				font-variant-numeric:tabular-nums;letter-spacing:-.02em;
			}
			.gpls-funnel-l{
				display:block;margin-top:4px;font-size:11px;line-height:1.3;
				text-transform:uppercase;letter-spacing:.06em;color:#a7f3ea;
			}
			.gpls-funnel-copy{flex:1 1 auto;min-width:0;}
			.gpls-funnel-title{
				margin:2px 0 6px!important;
				font-size:15px;font-weight:600;color:#0b2b28;line-height:1.35;
			}
			.gpls-funnel-body{
				margin:0 0 14px!important;
				font-size:13.5px;line-height:1.6;color:#44544f;max-width:78ch;
			}
			.gpls-funnel-actions{margin:0!important;display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
			.gpls-funnel-cta{
				display:inline-block;background:#0f766e;color:#fff!important;
				text-decoration:none;font-size:13px;font-weight:600;
				padding:8px 16px;border-radius:5px;line-height:1.4;
			}
			.gpls-funnel-cta:hover,.gpls-funnel-cta:focus{background:#0b5b55;color:#fff!important;}
			.gpls-funnel-cta:focus{outline:2px solid #0f766e;outline-offset:2px;}
			.gpls-funnel-hide{
				background:none;border:0;padding:0;cursor:pointer;
				font-size:13px;color:#6b7a76;text-decoration:underline;
			}
			.gpls-funnel-hide:hover{color:#0b2b28;}
			@media (max-width:600px){
				.gpls-funnel-in{flex-direction:column;gap:14px;}
				.gpls-funnel-stat{min-width:0;align-self:flex-start;padding:10px 16px;}
			}
			@media (prefers-color-scheme:dark){
				.gpls-funnel{background:#10201e;border-left-color:#2dd4bf;}
				.gpls-funnel-title{color:#e6fffb;}
				.gpls-funnel-body{color:#a8bfba;}
				.gpls-funnel-stat{background:#134e48;}
				.gpls-funnel-l{color:#7fe3d6;}
				.gpls-funnel-cta{background:#2dd4bf;color:#04201d!important;}
				.gpls-funnel-cta:hover,.gpls-funnel-cta:focus{background:#5eead4;color:#04201d!important;}
				.gpls-funnel-hide{color:#8aa39e;}
			}
			</style>
			<?php
		}

		/**
		 * Wire the dismiss button and the notice's own X to the same handler.
		 *
		 * @return void
		 */
		private static function print_dismiss_script() {
			$nonce = wp_create_nonce( 'gpls_funnel' );
			?>
			<script>
			( function() {
				// Bound to the data attribute the handler actually needs, not to
				// a class name: a styling rename previously broke this silently.
				var notices = document.querySelectorAll( '[data-gpls-offer]' );

				Array.prototype.forEach.call( notices, function( notice ) {
					function forget() {
						var data = new FormData();
						data.append( 'action', 'gpls_funnel_dismiss' );
						data.append( 'nonce', '<?php echo esc_js( $nonce ); ?>' );
						data.append( 'offer', notice.getAttribute( 'data-gpls-offer' ) );

						if ( window.fetch && window.ajaxurl ) {
							fetch( window.ajaxurl, {
								method: 'POST',
								body: data,
								credentials: 'same-origin'
							} );
						}
					}

					notice.addEventListener( 'click', function( e ) {
						var hide = e.target.closest ? e.target.closest( '.gpls-funnel-hide' ) : null;

						if ( hide ) {
							e.preventDefault();
							forget();
							if ( notice.parentNode ) {
								notice.parentNode.removeChild( notice );
							}
							return;
						}

						// WordPress injects its own dismiss button into .is-dismissible.
						if ( e.target.classList && e.target.classList.contains( 'notice-dismiss' ) ) {
							forget();
						}
					} );
				} );
			}() );
			</script>
			<?php
		}
	}
}
