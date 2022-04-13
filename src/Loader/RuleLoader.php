<?php

declare(strict_types=1);

namespace Ekok\Config\Loader;

use Ekok\Utils\Str;
use Ekok\Utils\Call;
use Ekok\Container\Di;
use Ekok\Validation\Validator;
use Ekok\Config\Attribute\Rule;
use Ekok\Validation\Rule as ValidatorRule;

class RuleLoader extends AbstractLoader
{
    public function __construct(private Di $di, private Validator $validator)
    {}

    public function loadClass(string|object $class): void
    {
        if (is_string($class) && is_subclass_of($class, ValidatorRule::class)) {
            $this->validator->addRule($class);

            return;
        }

        parent::loadClass($class);
    }

    protected function __loadRule(\ReflectionClass $class, array $methods): void
    {
        $attributes = $class->getAttributes(Rule::class);

        array_walk(
            $attributes,
            function (\ReflectionAttribute $attribute) use ($class) {
                /** @var Rule */
                $attr = $attribute->newInstance();

                $this->validator->addCustomRule(
                    $attr->name ?? Str::className($class->name, true),
                    $this->createRule(Call::standarize($class->name, '__invoke')),
                    $attr->message,
                );
            },
        );

        array_walk(
            $methods,
            function (\ReflectionMethod $method) use ($class) {
                /** @var Rule */
                $rule = self::getAttributeInstance(Rule::class, $method);

                $this->validator->addCustomRule(
                    $rule?->name ?? Str::caseSnake($method->name),
                    $this->createRule(
                        Call::standarize(
                            $class->name,
                            $method->name,
                            $method->isStatic(),
                        ),
                    ),
                    $rule?->message,
                );
            },
        );
    }

    private function createRule(string $call): \Closure
    {
        $di = $this->di;

        return fn (...$args) => $di->callArguments($call, $args);
    }
}
