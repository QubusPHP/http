<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Session;

trait FlashAware
{
    /**
     * Add an info message
     *
     * @param  string  $message      The message text
     * @param  string  $redirectUrl  Where to redirect once the message is added
     * @param  bool $sticky       Sticky the message (hides the close button)
     * @return object
     */
    public function info(string $message, ?string $redirectUrl = null, bool $sticky = false)
    {
        return $this->add($message, MessageType::INFO, $redirectUrl, $sticky);
    }

    /**
     * Add a success message
     *
     * @param  string  $message      The message text
     * @param  string  $redirectUrl  Where to redirect once the message is added
     * @param  bool $sticky       Sticky the message (hides the close button)
     * @return object
     */
    public function success(string $message, ?string $redirectUrl = null, bool $sticky = false)
    {
        return $this->add($message, MessageType::SUCCESS, $redirectUrl, $sticky);
    }

    /**
     * Add a warning message
     *
     * @param  string  $message      The message text
     * @param  string  $redirectUrl  Where to redirect once the message is added
     * @param  bool $sticky       Sticky the message (hides the close button)
     * @return object
     */
    public function warning(string $message, ?string $redirectUrl = null, bool $sticky = false)
    {
        return $this->add($message, MessageType::WARNING, $redirectUrl, $sticky);
    }

    /**
     * Add an error message
     *
     * @param  string  $message      The message text
     * @param  string  $redirectUrl  Where to redirect once the message is added
     * @param  bool $sticky       Sticky the message (hides the close button)
     * @return object
     */
    public function error(string $message, ?string $redirectUrl = null, bool $sticky = false)
    {
        return $this->add($message, MessageType::ERROR, $redirectUrl, $sticky);
    }

    /**
     * Add a sticky message
     *
     * @param  string  $message      The message text
     * @param  string  $redirectUrl  Where to redirect once the message is added
     * @param  string  $type         The $msgType
     * @return object
     */
    public function sticky(string $message, ?string $redirectUrl = null, string $type = MessageType::DEFAULT)
    {
        return $this->add($message, $type, $redirectUrl, true);
    }

    /**
     * Add a flash message to the session data
     *
     * @param  string  $message      The message text
     * @param  string  $type         The $msgType
     * @param  string  $redirectUrl  Where to redirect once the message is added
     * @param  bool $sticky       Whether or not the message is stickied
     * @return object
     */
    public function add(
        string $message,
        string $type = MessageType::DEFAULT,
        ?string $redirectUrl = null,
        bool $sticky = false
    ) {
        // Make sure a message and valid type was passed
        if (! isset($message[0])) {
            return false;
        }
        if (strlen(trim($type)) > 1) {
            $type = strtolower($type[0]);
        }
        if (! array_key_exists($type, $this->msgTypes)) {
            $type = MessageType::DEFAULT;
        }

        // Add the message to the session data
        if (! array_key_exists($type, $this->session->get('flash'))) {
            $_SESSION['flash'][$type] = [];
        }
        $_SESSION['flash'][$type][] = ['sticky' => $sticky, 'message' => $message];

        // Handle the redirect if needed
        if (null !== $redirectUrl) {
            $this->redirectUrl = $redirectUrl;
        }
        $this->doRedirect();

        return $this;
    }
}
