<?php

namespace Dragon\helpers;

use Dragon\controllers\IController;
use Dragon\http\RequestMethod;

class RouteTarget
{
    /**
     * @param string|null $controller
     * @param string $method
     * @param RequestMethod[] $verbs HTTP verb provided as string is converted into RequestMethod
     * @param array $vars Used internally to store variables extracted from the route pattern.
     */
    public function __construct(
        public ?string $controller = null {
            set (string|null|IController $value) {
                if (is_string($value)) {
                    $this->controller = \Dragon\helpers\Utils::normalizeClassName($value);
                } elseif ($value instanceof IController) {
                    $this->controller = $value::class;
                } else {
                    $this->controller = $value;
                }
            }
        },
        public string                  $method = 'index',
        public array                   $verbs = [RequestMethod::GET] {
            set {
                $set = [];
                foreach ($value as $item) {
                    if (is_string($item)) {
                        $item = RequestMethod::tryFrom(strtoupper($item));
                    }
                    if ($item instanceof RequestMethod) {
                        $set[] = $item;
                    }
                }
                $this->verbs = $set;
            }
        },
        public array                   $vars = []
    )
    {
    }
}
