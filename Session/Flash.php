<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Session;

use Qubus\Http\Factories\RedirectResponseFactory;
use Qubus\Http\Session\FlashAware;
use Qubus\Http\Session\MessageType;

use function array_key_exists;
use function array_keys;
use function is_array;
use function sha1;
use function sprintf;
use function strtolower;
use function uniqid;

class Flash
{
    use FlashAware;

    /** @var array $msgTypes */
    protected array $msgTypes = [
        MessageType::ERROR   => 'error',
        MessageType::WARNING => 'warning',
        MessageType::SUCCESS => 'success',
        MessageType::INFO    => 'info',
    ];

    /** @var string $msgWrapper */
    protected $msgWrapper = "<div class='%s'>%s</div>\n";

    protected string $msgBefore = '';

    protected string $msgAfter = '';

    protected string $closeBtn = '<button type="button" class="close"
                                data-dismiss="alert"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>';

    protected string $stickyCssClass = 'sticky';

    protected string $msgCssClass = 'alert dismissable';

    /** @var string $cssClassMap */
    protected array $cssClassMap = [
        MessageType::INFO    => 'alert-info center',
        MessageType::SUCCESS => 'alert-success center',
        MessageType::WARNING => 'alert-warning center',
        MessageType::ERROR   => 'alert-danger center',
    ];

    protected ?string $redirectUrl = null;

    public readonly string $msgId;

    public function __construct(public readonly PhpSession $session)
    {
        // Generate a unique ID for this user and session
        $this->msgId = sha1(uniqid());
        // Create session array to hold our messages if it doesn't already exist
        if (! array_key_exists('flash', $this->session->getAll())) {
            $this->session->set('flash', []);
        }
    }

    /**
     * Notice messages.
     *
     * @return string
     */
    public function notice(int $num)
    {
        $msg[200] = '200 - Success: Ok';
        $msg[201] = '201 - Success: Created';
        $msg[204] = '204 - Error: No Content';
        $msg[409] = '409 - Error: Conflict';

        return $msg[$num];
    }

    /**
     * Display the flash messages
     *
     * @param  mixed   $types   (null)  print all of the message types
     *                          (array)  print the given message types
     *                          (string)   print a single message type
     * @param  bool $print   Whether to print the data or return it
     * @return string
     */
    public function display($types = null, bool $print = true)
    {
        if (! $this->session->has('flash')) {
            return false;
        }

        $output = '';

        // Print all the message types
        if (null === $types || ! $types || (is_array($types) && empty($types))) {
            $types = array_keys($this->msgTypes);

        // Print multiple message types (as defined by an array)
        } elseif (is_array($types) && ! empty($types)) {
            $theTypes = $types;
            $types = [];
            foreach ($theTypes as $type) {
                $types[] = strtolower($type[0]);
            }
            // Print only a single message type
        } else {
            $types = [strtolower($types[0])];
        }

        // Retrieve and format the messages, then remove them from session data
        foreach ($types as $type) {
            if (! $this->session->has('flash') || empty($this->session->get('flash')[$type])) {
                continue;
            }

            foreach ($this->session->get('flash')[$type] as $msgData) {
                $output .= $this->formatMessage($msgData, $type);
            }
            $this->clear($type);
        }

        // Print everything to the screen (or return the data)
        if ($print) {
            echo $output;
        } else {
            return $output;
        }
    }

    /**
     * See if there are any queued error messages
     *
     * @return bool
     */
    public function hasErrors()
    {
        return empty($this->session->get('flash')[MessageType::ERROR]) ? false : true;
    }

    /**
     * See if there are any queued message
     *
     * @param  string  $type  The $msgType
     * @return bool
     */
    public function hasMessages(?string $type = null)
    {
        if (null !== $type) {
            if (! empty($this->session->get('flash')[$type])) {
                return $this->session->get('flash')[$type];
            }
        } else {
            foreach (array_keys($this->msgTypes) as $type) {
                if ($this->session->has('flash') && ! empty($this->session->get('flash')[$type])) {
                    return $this->session->get('flash')[$type];
                }
            }
        }
        return false;
    }

    /**
     * Format a message
     *
     * @param  array  $msgDataArray   Array of message data
     * @param  string $type           The $msgType
     * @return string                 The formatted message
     */
    protected function formatMessage($msgDataArray, $type)
    {
        $msgType = isset($this->msgTypes[$type]) ? $type : MessageType::DEFAULT;
        $cssClass = $this->msgCssClass . ' ' . $this->cssClassMap[$type];
        $msgBefore = $this->msgBefore;

        // If sticky then append the sticky CSS class
        if ($msgDataArray['sticky']) {
            $cssClass .= ' ' . $this->stickyCssClass;

        // If it's not sticky then add the close button
        } else {
            $msgBefore = $this->closeBtn . $msgBefore;
        }

        // Wrap the message if necessary
        $formattedMessage = $msgBefore . $msgDataArray['message'] . $this->msgAfter;

        return sprintf(
            $this->msgWrapper,
            $cssClass,
            $formattedMessage
        );
    }

    /**
     * Redirect the user if a URL was given.
     *
     * @return object
     */
    protected function doRedirect()
    {
        if ($this->redirectUrl) {
            RedirectResponseFactory::create($this->redirectUrl);
            exit();
        }
        return $this;
    }

    /**
     * Clear the messages from the session data
     *
     * @param  mixed  $types  (array) Clear all of the message types in array
     *                        (string)  Only clear the one given message type
     * @return object
     */
    protected function clear($types = [])
    {
        if ((is_array($types) && empty($types)) || null === $types || ! $types) {
            $this->session->unsetSession('flash');
        } elseif (! is_array($types)) {
            $types = [$types];
        }

        foreach ($types as $type) {
            unset($_SESSION['flash'][$type]);
        }

        return $this;
    }

    /**
     * Set the HTML that each message is wrapped in
     *
     * @param string $msgWrapper The HTML that each message is wrapped in.
     *                           Note: Two placeholders (%s) are expected.
     *                           The first is the $msgCssClass,
     *                           The second is the message text.
     * @return object
     */
    public function setMsgWrapper($msgWrapper = '')
    {
        $this->msgWrapper = $msgWrapper;
        return $this;
    }

    /**
     * Prepend string to the message (inside of the message wrapper)
     *
     * @param string $msgBefore string to prepend to the message
     * @return object
     */
    public function setMsgBefore($msgBefore = '')
    {
        $this->msgBefore = $msgBefore;
        return $this;
    }

    /**
     * Append string to the message (inside of the message wrapper)
     *
     * @param string $msgAfter string to append to the message
     * @return object
     */
    public function setMsgAfter($msgAfter = '')
    {
        $this->msgAfter = $msgAfter;
        return $this;
    }

    /**
     * Set the HTML for the close button
     *
     * @param string  $closeBtn  HTML to use for the close button
     * @return object
     */
    public function setCloseBtn($closeBtn = '')
    {
        $this->closeBtn = $closeBtn;
        return $this;
    }

    /**
     * Set the CSS class for sticky notes
     *
     * @param string  $stickyCssClass  the CSS class to use for sticky messages
     * @return object
     */
    public function setStickyCssClass($stickyCssClass = '')
    {
        $this->stickyCssClass = $stickyCssClass;
        return $this;
    }

    /**
     * Set the CSS class for messages
     *
     * @param string $msgCssClass The CSS class to use for messages
     * @return object
     */
    public function setMsgCssClass($msgCssClass = '')
    {
        $this->msgCssClass = $msgCssClass;
        return $this;
    }

    /**
     * Set the CSS classes for message types
     *
     * @param mixed  $msgType    (string) The message type
     *                           (array) key/value pairs for the class map
     * @param mixed  $cssClass   (string) the CSS class to use
     *                           (null) not used when $msgType is an array
     * @return object
     */
    public function setCssClassMap($msgType, $cssClass = null)
    {
        if (! is_array($msgType)) {
            // Make sure there's a CSS class set
            if (null === $cssClass) {
                return $this;
            }
            $msgType = [$msgType => $cssClass];
        }

        foreach ($msgType as $type => $cssClass) {
            $this->cssClassMap[$type] = $cssClass;
        }

        return $this;
    }
}
