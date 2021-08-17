<?php
/**
 * Test AMP_Script_Sanitizer.
 *
 * @package AMP
 */

use AmpProject\AmpWP\Dom\Options;
use AmpProject\AmpWP\Tests\Helpers\MarkupComparison;
use AmpProject\AmpWP\Tests\TestCase;
use AmpProject\Dom\Document;
use AmpProject\Extension;
use AmpProject\Tag;

/**
 * Test AMP_Script_Sanitizer.
 *
 * @covers AMP_Script_Sanitizer
 */
class AMP_Script_Sanitizer_Test extends TestCase {

	use MarkupComparison;

	/**
	 * Data for testing noscript handling.
	 *
	 * @return array
	 */
	public function get_sanitizer_data() {
		return [
			'document_write'                => [
				'<html><head><meta charset="utf-8"></head><body>Has script? <script>document.write("Yep!")</script><noscript>Nope!</noscript></body></html>',
				'<html><head><meta charset="utf-8"></head><body>Has script? <!--noscript-->Nope!<!--/noscript--></body></html>',
				[],
				[ AMP_Tag_And_Attribute_Sanitizer::DISALLOWED_TAG ],
			],
			'nested_elements'               => [
				'<html><head><meta charset="utf-8"></head><body><noscript>before <em><strong>middle</strong> end</em></noscript></body></html>',
				'<html><head><meta charset="utf-8"></head><body><!--noscript-->before <em><strong>middle</strong> end</em><!--/noscript--></body></html>',
			],
			'head_noscript_style'           => [
				'<html><head><meta charset="utf-8"><noscript><style>body{color:red}</style></noscript></head><body></body></html>',
				'<html><head><meta charset="utf-8"><!--noscript--><style>body{color:red}</style><!--/noscript--></head><body></body></html>',
			],
			'head_noscript_span'            => [
				'<html><head><meta charset="utf-8"><noscript><span>No script</span></noscript></head><body></body></html>',
				'<html><head><meta charset="utf-8"></head><body><!--noscript--><span>No script</span><!--/noscript--></body></html>',
			],
			'test_with_dev_mode'            => [
				'<html data-ampdevmode=""><head><meta charset="utf-8"></head><body><noscript data-ampdevmode="">hey</noscript></body></html>',
				null,
			],
			'noscript_no_unwrap_attr'       => [
				'<html><head><meta charset="utf-8"></head><body><noscript data-amp-no-unwrap><span>No script</span></noscript></body></html>',
				null,
			],
			'noscript_no_unwrap_arg'        => [
				'<html><head><meta charset="utf-8"></head><body><noscript><span>No script</span></noscript></body></html>',
				null,
				[
					'unwrap_noscripts' => false,
				],
			],
			'script_kept_no_unwrap'         => [
				'
					<html><head><meta charset="utf-8"></head><body>
						<script>document.write("Hey.")</script>
						<noscript data-amp-no-unwrap><span>No script</span></noscript>
					</body></html>',
				'
					<html data-ampdevmode=""><head><meta charset="utf-8"></head><body>
						<script data-ampdevmode="">document.write("Hey.")</script>
						<noscript data-amp-no-unwrap><span>No script</span></noscript>
					</body></html>
				',
				[
					'sanitize_scripts'          => true,
					'unwrap_noscripts'          => true, // This will be ignored because of the kept script.
					'validation_error_callback' => '__return_false',
				],
				[
					AMP_Script_Sanitizer::CUSTOM_INLINE_SCRIPT,
				],
			],
			'inline_scripts_removed'        => [
				'
					<html><head><meta charset="utf-8"></head><body>
						<script>document.write("Hey.")</script>
						<script type="application/json">{"data":1}</script>
						<script type="application/ld+json">{"data":2}</script>
						<amp-state id="test"><script type="application/json">{"data":3}</script></amp-state>
					</body></html>
				',
				'
					<html><head><meta charset="utf-8"></head><body>
					<script type="application/ld+json">{"data":2}</script>
					<amp-state id="test"><script type="application/json">{"data":3}</script></amp-state>
					</body></html>
				',
				[
					'sanitize_scripts' => true,
				],
				[
					AMP_Script_Sanitizer::CUSTOM_INLINE_SCRIPT,
					AMP_Tag_And_Attribute_Sanitizer::DISALLOWED_TAG,
				],
			],
			'external_scripts_removed'      => [
				'
					<html>
					<head><meta charset="utf-8"></head>
					<body>
						<script src="https://example.com/1"></script>
						<script type="text/javascript" src="https://example.com/2"></script>
						<script type="module" src="https://example.com/3"></script>
					</body></html>
				',
				'
					<html>
					<head><meta charset="utf-8"></head>
					<body></body></html>
				',
				[
					'sanitize_scripts' => true,
				],
				[
					AMP_Script_Sanitizer::CUSTOM_EXTERNAL_SCRIPT,
					AMP_Script_Sanitizer::CUSTOM_EXTERNAL_SCRIPT,
					AMP_Script_Sanitizer::CUSTOM_EXTERNAL_SCRIPT,
				],
			],
			'external_amp_script_kept'      => [
				'
					<html>
					<head>
						<meta charset="utf-8">
						<script async src="https://cdn.ampproject.org/v0.js" crossorigin="anonymous"></script>
					</head>
					<body></body></html>
				',
				null,
				[
					'sanitize_scripts' => true,
				],
				[],
			],
			'amp_onerror_script_kept'       => [
				'
					<html>
					<head>
						<meta charset="utf-8">
						<script amp-onerror>document.querySelector("script[src*=\'/v0.js\']").onerror=function(){document.querySelector(\'style[amp-boilerplate]\').textContent=\'\'}</script>
					</head>
					<body></body></html>
				',
				null,
				[
					'sanitize_scripts' => true,
				],
				[],
			],
			'inline_event_handler_removed'  => [
				'
					<html><head><meta charset="utf-8"></head>
					<body onload="alert(\'Hey there.\')">
						<noscript>I should get unwrapped.</noscript>
						<div id="warning-message">Warning...</div>
						<button on="tap:warning-message.hide">Cool, thanks!</button>
					</body></html>
				',
				'
					<html><head><meta charset="utf-8"></head>
					<body>
						<!--noscript-->I should get unwrapped.<!--/noscript-->
						<div id="warning-message">Warning...</div>
						<button on="tap:warning-message.hide">Cool, thanks!</button>
					</body></html>
				',
				[
					'sanitize_scripts' => true,
				],
				[
					AMP_Script_Sanitizer::CUSTOM_EVENT_HANDLER_ATTR,
				],
			],
			'inline_event_handler_kept'     => [
				'
					<html><head><meta charset="utf-8"></head>
					<body onload="alert(\'Hey there.\')">
						<noscript>I should not get unwrapped.</noscript>
						<div id="warning-message">Warning...</div>
						<button on="tap:warning-message.hide">Cool, thanks!</button>
					</body></html>
				',
				'
					<html data-ampdevmode><head><meta charset="utf-8"></head>
					<body data-ampdevmode onload="alert(\'Hey there.\')">
						<noscript>I should not get unwrapped.</noscript>
						<div id="warning-message">Warning...</div>
						<button on="tap:warning-message.hide">Cool, thanks!</button>
					</body></html>
				',
				[
					'sanitize_scripts'          => true,
					'validation_error_callback' => '__return_false',
				],
				[
					AMP_Script_Sanitizer::CUSTOM_EVENT_HANDLER_ATTR,
				],
			],
			'event_handler_lookalikes_kept' => [
				'
					<html><head><meta charset="utf-8"></head>
					<body>
						<noscript>I should get unwrapped.</noscript>
						<div id="warning-message">Warning...</div>
						<button on="tap:warning-message.hide">Cool, thanks!</button>
						<amp-position-observer intersection-ratios="0.5" on="enter:clockAnim.start;exit:clockAnim.pause" layout="nodisplay" once></amp-position-observer>
						<amp-font layout="nodisplay" font-family="My Font" timeout="3000" on-error-remove-class="my-font-loading" on-error-add-class="my-font-missing"></amp-font>
					</body></html>
				',
				'
					<html><head><meta charset="utf-8"></head>
					<body>
						<!--noscript-->I should get unwrapped.<!--/noscript-->
						<div id="warning-message">Warning...</div>
						<button on="tap:warning-message.hide">Cool, thanks!</button>
						<amp-position-observer intersection-ratios="0.5" on="enter:clockAnim.start;exit:clockAnim.pause" layout="nodisplay" once></amp-position-observer>
						<amp-font layout="nodisplay" font-family="My Font" timeout="3000" on-error-remove-class="my-font-loading" on-error-add-class="my-font-missing"></amp-font>
					</body></html>
				',
				[
					'sanitize_scripts' => true,
				],
				[],
			],
		];
	}

	/**
	 * Test that noscript elements get replaced with their children.
	 *
	 * @dataProvider get_sanitizer_data
	 * @param string $source        Source.
	 * @param string $expected      Expected.
	 * @param array $sanitizer_args Sanitizer args.
	 * @covers AMP_Script_Sanitizer::sanitize()
	 */
	public function test_sanitize( $source, $expected = null, $sanitizer_args = [], $expected_error_codes = [] ) {
		if ( null === $expected ) {
			$expected = $source;
		}
		$dom = Document::fromHtml( $source, Options::DEFAULTS );
		$this->assertSame( substr_count( $source, '<noscript' ), $dom->getElementsByTagName( 'noscript' )->length );

		$validation_error_callback_arg = isset( $sanitizer_args['validation_error_callback'] ) ? $sanitizer_args['validation_error_callback'] : null;

		$actual_error_codes = [];

		$sanitizer_args['validation_error_callback'] = static function ( $error ) use ( &$actual_error_codes, $validation_error_callback_arg ) {
			$actual_error_codes[] = $error['code'];

			if ( $validation_error_callback_arg ) {
				return $validation_error_callback_arg();
			} else {
				return true;
			}
		};

		$sanitizer = new AMP_Script_Sanitizer( $dom, $sanitizer_args );
		$sanitizer->sanitize();
		$validating_sanitizer = new AMP_Tag_And_Attribute_Sanitizer( $dom, $sanitizer_args );
		$validating_sanitizer->sanitize();
		$content = $dom->saveHTML( $dom->documentElement );
		$this->assertSimilarMarkup( $expected, $content );

		$this->assertSame( $expected_error_codes, $actual_error_codes );
	}

	/** @return array */
	public function get_data_to_test_cascading_sanitizer_argument_changes_with_custom_scripts() {
		return [
			'custom_scripts_removed' => [ true ],
			'custom_scripts_kept'    => [ false ],
		];
	}

	/**
	 * @dataProvider get_data_to_test_cascading_sanitizer_argument_changes_with_custom_scripts
	 *
	 * @covers AMP_Script_Sanitizer::init()
	 *
	 * @param bool $remove_custom_scripts Remove custom scripts.
	 */
	public function test_cascading_sanitizer_argument_changes_with_custom_scripts( $remove_custom_scripts ) {
		$dom = Document::fromHtml(
			'
			<html>
				<head>
					<style>body { background: red; } body.loaded { background: green; }</style>
				</head>
				<body>
					<img src="https://example.com/logo.png" width="300" height="100" alt="Logo">
					<script>document.addEventListener("DOMContentLoaded", () => document.body.classList.add("loaded"))</script>
				</body>
			</html>
			',
			Options::DEFAULTS
		);

		$sanitizers = [
			AMP_Script_Sanitizer::class => new AMP_Script_Sanitizer(
				$dom,
				[
					'sanitize_scripts'          => true,
					'validation_error_callback' => function () use ( $remove_custom_scripts ) {
						return $remove_custom_scripts;
					},
				]
			),
			AMP_Img_Sanitizer::class    => new AMP_Img_Sanitizer(
				$dom,
				[
					'native_img_used' => false, // Overridden by AMP_Script_Sanitizer when there is a kept script.
				]
			),
			AMP_Style_Sanitizer::class  => new AMP_Style_Sanitizer(
				$dom,
				[
					'use_document_element' => true,
					'skip_tree_shaking'    => false, // Overridden by AMP_Script_Sanitizer when there is a kept script.
				]
			),
		];

		/** @var AMP_Base_Sanitizer $sanitizer */
		foreach ( $sanitizers as $sanitizer ) {
			$sanitizer->init( $sanitizers );
		}

		foreach ( $sanitizers as $sanitizer ) {
			$sanitizer->sanitize();
		}

		$this->assertEquals(
			$remove_custom_scripts ? 0 : 1,
			$dom->getElementsByTagName( Tag::SCRIPT )->length
		);
		$this->assertEquals(
			$remove_custom_scripts ? 1 : 0,
			$dom->getElementsByTagName( Extension::IMG )->length,
			'Expected <img> to be converted to <amp-img> when custom scripts are removed.'
		);

		$style = $dom->getElementsByTagName( Tag::STYLE )->item( 0 );
		if ( $remove_custom_scripts ) {
			$this->assertStringStartsWith( "body{background:red}\n", $style->textContent );
		} else {
			$this->assertStringStartsWith( "body{background:red}body.loaded{background:green}\n", $style->textContent );
		}
	}

	/**
	 * Test style[amp-boilerplate] preservation.
	 */
	public function test_boilerplate_preservation() {
		ob_start();
		?>
		<!doctype html>
		<html amp>
			<head>
				<meta charset="utf-8">
				<link rel="canonical" href="self.html" />
				<meta name="viewport" content="width=device-width,minimum-scale=1">
				<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
				<script async src="https://cdn.ampproject.org/v0.js"></script><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>

				<!-- Google Tag Manager -->
				<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
				new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
				j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
				'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
				})(window,document,'script','dataLayer','GTM-XXXX');</script>
				<!-- End Google Tag Manager -->
			</head>
			<body>
				<!-- Google Tag Manager (noscript) -->
				<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-XXXX"
				height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
				<!-- End Google Tag Manager (noscript) -->

				Hello, AMP world.
				Has script? <script>document.write("Yep!")</script><noscript>Nope!</noscript>
			</body>
		</html>
		<?php
		$html = ob_get_clean();
		$args = [
			'use_document_element' => true,
		];

		$dom = Document::fromHtml( $html, Options::DEFAULTS );
		AMP_Content_Sanitizer::sanitize_document( $dom, amp_get_content_sanitizers(), $args );

		$content = $dom->saveHTML( $dom->documentElement );

		$this->assertMatchesRegularExpression( '/<!-- Google Tag Manager -->\s*<!-- End Google Tag Manager -->/', $content );
		$this->assertStringContainsString( '<noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>', $content );
		$this->assertStringContainsString( 'Has script? <!--noscript-->Nope!<!--/noscript-->', $content );
		$this->assertStringContainsString( '<!--noscript--><amp-iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXX" height="400" layout="fixed-height" width="auto" sandbox="allow-downloads allow-forms allow-modals allow-orientation-lock allow-pointer-lock allow-popups allow-popups-to-escape-sandbox allow-presentation allow-same-origin allow-scripts allow-top-navigation-by-user-activation" data-amp-original-style="display:none;visibility:hidden" class="amp-wp-b3bfe1b"><span placeholder="" class="amp-wp-iframe-placeholder"></span><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXX" height="0" width="0"></iframe></noscript></amp-iframe><!--/noscript-->', $content );
	}
}
