<?php
namespace InterNations\Component\HttpMock\Request;

use BadMethodCallException;
use Guzzle\Common\Collection;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Header;
use Guzzle\Http\Message\Header\HeaderCollection;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\QueryString;

final class UnifiedRequest
{
    private RequestInterface $wrapped;

    private ?string $userAgent = null;

    /** @param array<string,mixed> $params */
    public function __construct(RequestInterface $wrapped, array $params = [])
    {
        $this->wrapped = $wrapped;
        $this->init($params);
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Get the body of the request if set
     *
     */
    public function getBody(): ?EntityBodyInterface
    {
        return $this->invokeWrappedIfEntityEnclosed(__FUNCTION__);
    }

    /**
     * Get a POST field from the request
     *
     * @param string $field Field to retrieve
     *
     * @return mixed|null
     */
    public function getPostField(string $field)
    {
        return $this->invokeWrappedIfEntityEnclosed(__FUNCTION__, [$field]);
    }

    /**
     * Get the post fields that will be used in the request
     *
     */
    public function getPostFields(): QueryString
    {
        return $this->invokeWrappedIfEntityEnclosed(__FUNCTION__);
    }

    /**
     * Returns an associative array of POST field names to PostFileInterface objects
     *
     * @return array
     */
    public function getPostFiles(): array
    {
        return $this->invokeWrappedIfEntityEnclosed(__FUNCTION__);
    }

    /**
     * Get a POST file from the request
     *
     * @param string $fieldName POST fields to retrieve
     *
     * @return array|null Returns an array wrapping an array of PostFileInterface objects
     */
    public function getPostFile(string $fieldName): ?array
    {
        return $this->invokeWrappedIfEntityEnclosed(__FUNCTION__, [$fieldName]);
    }

    /**
     * Get application and plugin specific parameters set on the message.
     *
     */
    public function getParams(): Collection
    {
        return $this->wrapped->getParams();
    }

    /**
     * Retrieve an HTTP header by name. Performs a case-insensitive search of all headers.
     *
     * @param string $header Header to retrieve.
     *
     * @return Header|null Returns NULL if no matching header is found.
     *                     Returns a Header object if found.
     */
    public function getHeader(string $header): ?Header
    {
        return $this->wrapped->getHeader($header);
    }

    /**
     * Get all headers as a collection
     *
     */
    public function getHeaders(): HeaderCollection
    {
        return $this->wrapped->getHeaders();
    }

    /**
     * Get an array of message header lines
     *
     * @return array
     */
    public function getHeaderLines(): array
    {
        return $this->wrapped->getHeaderLines();
    }

    /**
     * Check if the specified header is present.
     *
     * @param string $header The header to check.
     *
     * @return bool Returns TRUE or FALSE if the header is present
     */
    public function hasHeader(string $header): bool
    {
        return $this->wrapped->hasHeader($header);
    }

    /**
     * Get the raw message headers as a string
     *
     */
    public function getRawHeaders(): string
    {
        return $this->wrapped->getRawHeaders();
    }

    /**
     * Get the collection of key value pairs that will be used as the query
     * string in the request
     *
     */
    public function getQuery(): QueryString
    {
        return $this->wrapped->getQuery();
    }

    /**
     * Get the HTTP method of the request
     *
     */
    public function getMethod(): string
    {
        return $this->wrapped->getMethod();
    }

    /**
     * Get the URI scheme of the request (http, https, ftp, etc)
     *
     */
    public function getScheme(): string
    {
        return $this->wrapped->getScheme();
    }

    /**
     * Get the host of the request
     *
     */
    public function getHost(): string
    {
        return $this->wrapped->getHost();
    }

    /**
     * Get the HTTP protocol version of the request
     *
     */
    public function getProtocolVersion(): string
    {
        return $this->wrapped->getProtocolVersion();
    }

    /**
     * Get the path of the request (e.g. '/', '/index.html')
     *
     */
    public function getPath(): string
    {
        return $this->wrapped->getPath();
    }

    /**
     * Get the port that the request will be sent on if it has been set
     *
     */
    public function getPort(): ?int
    {
        return $this->wrapped->getPort();
    }

    /**
     * Get the username to pass in the URL if set
     *
     */
    public function getUsername(): ?string
    {
        return $this->wrapped->getUsername();
    }

    /**
     * Get the password to pass in the URL if set
     *
     */
    public function getPassword(): ?string
    {
        return $this->wrapped->getPassword();
    }

    /**
     * Get the full URL of the request (e.g. 'http://www.guzzle-project.com/')
     * scheme://username:password@domain:port/path?query_string#fragment
     *
     * @param bool $asObject Set to TRUE to retrieve the URL as a clone of the URL object owned by the request.
     *
     * @return string|Url
     */
    public function getUrl(bool $asObject = false)
    {
        return $this->wrapped->getUrl($asObject);
    }

    /**
     * Get an array of Cookies
     *
     * @return array
     */
    public function getCookies(): array
    {
        return $this->wrapped->getCookies();
    }

    /**
     * Get a cookie value by name
     *
     * @param string $name Cookie to retrieve
     *
     */
    public function getCookie(string $name): ?string
    {
        return $this->wrapped->getCookie($name);
    }

    /**
     * @param array<mixed> $params
     * @return mixed
     */
    protected function invokeWrappedIfEntityEnclosed(string $method, array $params = [])
    {
        if (!$this->wrapped instanceof EntityEnclosingRequestInterface) {
            throw new BadMethodCallException(
                sprintf(
                    'Cannot call method "%s" on a request that does not enclose an entity.'
                    . ' Did you expect a POST/PUT request instead of %s %s?',
                    $method,
                    $this->wrapped->getMethod(),
                    $this->wrapped->getPath()
                )
            );
        }

        return call_user_func_array([$this->wrapped, $method], $params);
    }

    /** @param array<string, mixed> $params */
    private function init(array $params): void
    {
        foreach ($params as $property => $value) {
            if (!property_exists($this, $property)) {
                continue;
            }

            $this->{$property} = $value;
        }
    }
}
