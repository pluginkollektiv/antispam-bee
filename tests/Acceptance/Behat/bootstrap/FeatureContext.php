<?php
use PaulGibbs\WordpressBehatExtension\Context\RawWordpressContext;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Mink\Exception\ExpectationException;
use \Behat\Mink\Exception\UnsupportedDriverActionException;

/**
 * Define application features from the specific context.
 */
class FeatureContext extends RawWordpressContext implements SnippetAcceptingContext {
	use \PaulGibbs\WordpressBehatExtension\Context\Traits\UserAwareContextTrait;

    /**
     * Initialise context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the context constructor through behat.yml.
     */
    public function __construct() {
        parent::__construct();
    }

    private $options;

    /**
     * @Then I wait :seconds seconds
     */
    public function iWaitSeconds( $seconds ) {
        $this->getSession()->wait( $seconds * 1000 );
    }

	/**
	 * @Given the option :option has the value :value
	 */
	public function theOptionhasTheValue($option, $value)
	{

		$options = $this->defaultOptions();
		$options[$option] = $value;
		$this->updateOptions($options);
	}

	/**
	 * @Given the option :option has the array value :value
	 */
	public function theOptionhasTheArrayValue($option, $value)
	{
		$value = explode(',', $value);
		$options = $this->defaultOptions();
		$options[$option] = $value;
		$this->updateOptions($options);
	}

	/**
	 * @Then the value of the option :option is :value
	 */
	public function theValueOfTheOptionIs($key, $value)
	{
		$wpcli_args = [
			"'antispam_bee'",
			'--format=json',
		];
		$options = $this->getDriver()->wpcli('option', 'get', $wpcli_args );
		$options = json_decode($options['stdout']);
		if( $options->{ $key } != $value ) {
			throw new \Exception('values do not match.');
		}
	}

	/**
	 * @Given the option :option is set
	 */
	public function theOptionIsSet($option)
	{
		$option = array_map('trim',explode(',', $option));
		$options = $this->defaultOptions();
		foreach( $options as $key => $val ) {
			if( ! is_int( $val ) ) {
				continue;
			}
			$options[$key] = 0;
			if( in_array($key, $option, true) ) {
				$options[$key] = 1;
			}
		}
		$this->updateOptions($options);
	}

	private function updateOptions($options) {

		$this->options = $options;
		$options = "'" . json_encode($options) . "'";

		$wpcli_args = [
			'antispam_bee',
			'--format=json',
			$options,
		];
		$this->getDriver()->wpcli('option', 'set', $wpcli_args );
	}

	/**
	 * @Given the option :option is not set
	 */
	public function theOptionIsNotSet($option)
	{
		$option = array_map('trim',explode(',', $option));
		$options = $this->defaultOptions();
		foreach( $options as $key => $val ) {
			if( ! is_int( $val ) || ! in_array($key, $option, true) ) {
				continue;
			}
			$options[$key] = 0;
		}
		$options = "'" . json_encode($options) . "'";

		$wpcli_args = [
			'antispam_bee',
			'--format=json',
			$options,
		];
		$this->getDriver()->wpcli('option', 'set', $wpcli_args );
	}

	private function defaultOptions() {

		if( ! empty( $this->options ) ) {
			return $this->options;
		}
		$this->options = [
				// General
				'advanced_check' 	=> 1,
				'regexp_check'		=> 1,
				'spam_ip' 			=> 1,
				'already_commented'	=> 1,
				'gravatar_check'	=> 0,
				'time_check'		=> 0,
				'ignore_pings' 		=> 0,
				'always_allowed' 	=> 0,

				'dashboard_chart' 	=> 0,
				'dashboard_count' 	=> 0,

				// Filter
				'country_code' 		=> 0,
				'country_black'		=> '',
				'country_white'		=> '',

				'translate_api' 	=> 0,
				'translate_lang'	=> array(),

				'bbcode_check'		=> 1,

				// Advanced
				'flag_spam' 		=> 1,
				'email_notify' 		=> 0,
				'no_notice' 		=> 0,
				'cronjob_enable' 	=> 0,
				'cronjob_interval'	=> 0,

				'ignore_filter' 	=> 0,
				'ignore_type' 		=> 0,

				'reasons_enable'	=> 0,
				'ignore_reasons'	=> array()
			];

			return $this->options;
	}

	/**
	 * @When /^I hover over the element "([^"]*)"$/
	 */
	public function iHoverOverTheElement($locator)
	{
		$session = $this->getSession(); // get the mink session
		$element = $session->getPage()->find('css', $locator); // runs the actual query and returns the element

		// errors must not pass silently
		if (null === $element) {
			throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
		}

		// ok, let's hover it
		$element->mouseOver();
	}

    /** @AfterStep */
    public function afterStep(\Behat\Behat\Hook\Scope\AfterStepScope $scope)
    {

        try {
            $text = $this->getSession()->getPage()->getHTML();
        } catch (\Behat\Mink\Exception\DriverException $e) {
            $text = '';
        } catch (\WebDriver\Exception\NoSuchElement $e) {
            $text = '';
        }
        $errorMsg = '';
        if (preg_match( '^' . preg_quote('<b>Fatal error</b>:') . '^', $text)) {
            $errorMsg = 'PHP error message detected.';
        }
        if (preg_match( '^' . preg_quote('<b>Notice</b>:') . '^', $text)) {
            $errorMsg = 'PHP Notice message detected.';
        }

        if (!$errorMsg && $scope->getTestResult()->isPassed()) {
            return;
        }

        $failedStepsDir = dirname( __DIR__ ) . '/failed-steps/';
        if (! is_writable($failedStepsDir)) {
            @mkdir($failedStepsDir);
        }
        $featureFile = strtolower(str_replace(' ', '-', $featureFile = $scope->getFeature()->getTitle()));

        $line = $scope->getStep()->getLine();

        $errorFile = $failedStepsDir . '' . $featureFile . '-' . $line;
        echo PHP_EOL . 'ErrorFile: ' . $errorFile . PHP_EOL;
        file_put_contents($errorFile . '.html', $this->getSession()->getPage()->getHtml());

        echo file_get_contents($errorFile . '.html');
        if ($errorMsg) {
            throw new \Exception($errorMsg);
        }
    }
}
