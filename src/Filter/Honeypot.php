<?php
declare(strict_types=1);

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Entity\CommentDataTypes;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Filter\Preparer\PreparerInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

class Honeypot implements SpamFilterInterface
{

    const DID_RUN_INTO_HONEYPOT_KEY = 'ab_spam__hidden_field';

    /**
     * If already created, this will contain the OptionInterface.
     *
     * @var OptionInterface
     */
    private $options;

    /**
     * The factory will produce the option interface.
     *
     * @var OptionFactory
     */
    private $option_factory;

    /**
     * The honeypot preparer.
     *
     * @var PreparerInterface
     */
    private $preparer;

    /**
     * What type of data can be checked.
     *
     * @var array
     */
    private $types;

    public function __construct(OptionFactory $option_factory, PreparerInterface $preparer, array $types = [CommentDataTypes::COMMENT])
    {
        $this->option_factory = $option_factory;
        $this->preparer       = $preparer;
        $this->types = $types;
    }

    public function filter(DataInterface $data): float
    {
        if (isset($_POST[self::DID_RUN_INTO_HONEYPOT_KEY]) && !empty($_POST[self::DID_RUN_INTO_HONEYPOT_KEY])) {
            return 1;
        }

        return 0;
    }

    public function register(): bool
    {
        $did_register = $this->preparer->register(self::DID_RUN_INTO_HONEYPOT_KEY);
        return $did_register;
    }

    public function options(): OptionInterface
    {
        if ($this->options ) {
            return $this->options;
        }
        $args          = [
            'name'        => __('Honeypot', 'antispam-bee'),
            'description' => __('text.', 'antispam-bee'),
        ];
        $this->options = $this->option_factory->from_args($args);
        return $this->options;
    }

    public function id(): string
    {
        return 'honeypot';
    }

    public function can_check_data(DataInterface $data): bool
    {
        $can_check = in_array($data->type(), $this->types, true);
        return $can_check;
    }
}