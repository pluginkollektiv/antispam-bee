<?php
/**
 * Result of a spam check.
 *
 * @package AntispamBee\Api
 */

namespace AntispamBee\Api;

/**
 * Immutable value object describing the outcome of a spam check.
 *
 * Instances are created by {@see SpamCheck::check()}. Third-party code should
 * not construct them directly.
 *
 * @since 3.0.0
 */
final class CheckResult {

	/**
	 * Whether the item was classified as spam.
	 *
	 * @var bool
	 */
	private $is_spam;

	/**
	 * Slugs of the rules that classified the item as spam.
	 *
	 * @var string[]
	 */
	private $reasons;

	/**
	 * The normalized payload the rules were applied to.
	 *
	 * @var array<string, mixed>
	 */
	private $payload;

	/**
	 * Whether any rule was actually evaluated.
	 *
	 * @var bool
	 */
	private $was_evaluated;

	/**
	 * Constructor.
	 *
	 * @param bool                 $is_spam       Whether the item is spam.
	 * @param string[]             $reasons       Slugs of the rules that flagged the item.
	 * @param array<string, mixed> $payload       The normalized payload that was checked.
	 * @param bool                 $was_evaluated Whether any rule was evaluated.
	 */
	public function __construct( bool $is_spam, array $reasons, array $payload, bool $was_evaluated = true ) {
		$this->is_spam       = $is_spam;
		$this->reasons       = $reasons;
		$this->payload       = $payload;
		$this->was_evaluated = $was_evaluated;
	}

	/**
	 * Create a result for an item that could not be evaluated.
	 *
	 * @param array<string, mixed> $payload The normalized payload.
	 *
	 * @return self A result reporting no spam and no evaluation.
	 */
	public static function not_evaluated( array $payload ): self {
		return new self( false, [], $payload, false );
	}

	/**
	 * Whether the item was classified as spam.
	 *
	 * @return bool Whether the item is spam.
	 */
	public function is_spam(): bool {
		return $this->is_spam;
	}

	/**
	 * Whether any rule was evaluated.
	 *
	 * A result can report no spam simply because no rule was active for the
	 * reaction type. Callers that want to distinguish “checked and clean” from
	 * “not checked at all” — for example to warn an administrator that no rule
	 * is enabled — should consult this method.
	 *
	 * @return bool Whether at least one rule was evaluated.
	 */
	public function was_evaluated(): bool {
		return $this->was_evaluated;
	}

	/**
	 * Get the slugs of the rules that flagged the item as spam.
	 *
	 * These slugs are the stable representation, to be translated later. Persist
	 * these rather than the texts from {@see self::get_reason_texts()}.
	 *
	 * @return string[] A list of rule slugs.
	 */
	public function get_reasons(): array {
		return $this->reasons;
	}

	/**
	 * Get human-readable texts for the spam reasons.
	 *
	 * Only useful once the `init` action has run, because the underlying slug
	 * map is populated on `init`. When resolving reasons for display later (for
	 * example in an admin table), persist the slugs from
	 * {@see self::get_reasons()} and call this method at render time.
	 *
	 * @return string[] A list of reason texts.
	 */
	public function get_reason_texts(): array {
		return SpamCheck::get_reason_texts( $this->reasons );
	}

	/**
	 * Get the normalized payload the rules were applied to.
	 *
	 * Useful when passing the item on to {@see SpamCheck::post_process()}, and
	 * for debugging what the rules actually saw.
	 *
	 * @return array<string, mixed> The normalized payload.
	 */
	public function get_payload(): array {
		return $this->payload;
	}
}
