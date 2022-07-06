<?php

declare(strict_types=1);

namespace Atk4\Core;

use Atk4\Core\Translator\ITranslatorAdapter;

/**
 * All exceptions generated by Agile Core will use this class.
 */
class Exception extends \Exception
{
    use WarnDynamicPropertyTrait;

    /** @var array */
    public $params = [];

    /** @var string */
    protected $customExceptionTitle = 'Critical Error';

    /**
     * Most exceptions would be a cause by some other exception, Agile
     * Core will encapsulate them and allow you to access them anyway.
     *
     * @var array
     */
    private $trace2; // because PHP's use of final() sucks!

    /** @var string[] */
    private $solutions = []; // store solutions

    /** @var ITranslatorAdapter */
    private $adapter;

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        // save trace but skip constructors of this exception
        $trace = debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT);
        for ($i = 0; $i < count($trace); ++$i) {
            $c = $trace[$i];
            if (isset($c['object']) && $c['object'] === $this && $c['function'] === '__construct') {
                array_shift($trace);
            }
        }
        $this->trace2 = $trace;
    }

    /**
     * Change message (subject) of a current exception. Primary use is
     * for localization purposes.
     *
     * @return $this
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Return trace array.
     */
    public function getMyTrace(): array
    {
        return $this->trace2;
    }

    /**
     * Return exception message using color sequences.
     *
     * <exception name>: <string>
     * <info>
     *
     * trace
     *
     * --
     * <triggered by>
     */
    public function getColorfulText(): string
    {
        return (string) new ExceptionRenderer\Console($this, $this->adapter);
    }

    /**
     * Return exception message using HTML block and Semantic UI formatting. It's your job
     * to put it inside boilerplate HTML and output, e.g:.
     *
     *   $l = new \Atk4\Ui\App();
     *   $l->initLayout(\Atk4\Ui\Layout\Centered::class);
     *   $l->layout->template->setHtml('Content', $e->getHtml());
     *   $l->run();
     *   exit;
     */
    public function getHtml(): string
    {
        return (string) new ExceptionRenderer\Html($this, $this->adapter);
    }

    /**
     * Return exception in JSON Format.
     */
    public function getJson(): string
    {
        return (string) new ExceptionRenderer\Json($this, $this->adapter);
    }

    /**
     * Safely converts some value to string.
     *
     * @param mixed $val
     */
    public function toString($val): string
    {
        return ExceptionRenderer\RendererAbstract::toSafeString($val);
    }

    /**
     * Follow the getter-style of PHP Exception.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Augment existing exception with more info.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function addMoreInfo(string $param, $value): self
    {
        $this->params[$param] = $value;

        return $this;
    }

    /**
     * Add a suggested/possible solution to the exception.
     *
     * @return $this
     */
    public function addSolution(string $solution): self
    {
        $this->solutions[] = $solution;

        return $this;
    }

    /**
     * Get the solutions array.
     */
    public function getSolutions(): array
    {
        return $this->solutions;
    }

    /**
     * Get the custom Exception title, if defined in $customExceptionTitle.
     */
    public function getCustomExceptionTitle(): string
    {
        return $this->customExceptionTitle;
    }

    /**
     * Set Custom Translator adapter.
     *
     * @return $this
     */
    public function setTranslatorAdapter(ITranslatorAdapter $adapter = null): self
    {
        $this->adapter = $adapter;

        return $this;
    }
}
