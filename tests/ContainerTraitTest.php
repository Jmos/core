<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;
use Atk4\Core\Phpunit\TestCase;

class ContainerTraitTest extends TestCase
{
    public function testBasic(): void
    {
        $m = new ContainerMock();

        // add to return object
        $tr = $m->add($tr2 = new \stdClass());
        static::assertSame($tr, $tr2);

        // trackable object can be referenced by name
        $m->add($tr3 = new TrackableMock(), 'foo');
        $tr = $m->getElement('foo');
        static::assertSame($tr, $tr3);
    }

    public function testUniqueNames(): void
    {
        $m = new ContainerMock();

        // two anonymous children should get unique names asigned.
        $m->add(new TrackableMock());
        $anon = $m->add(new TrackableMock());
        $m->add(new TrackableMock(), 'foo bar');
        $m->add(new TrackableMock(), '123');
        $m->add(new TrackableMock(), 'false');

        static::assertTrue($m->hasElement('foo bar'));
        static::assertTrue($m->hasElement('123'));
        static::assertTrue($m->hasElement('false'));
        static::assertSame(5, $m->getElementCount());

        $m->getElement('foo bar')->destroy();
        static::assertSame(4, $m->getElementCount());
        $anon->destroy();
        static::assertSame(3, $m->getElementCount());
    }

    public function testLongNames(): void
    {
        $app = new ContainerAppMock();
        $app->setApp($app);
        $app->maxNameLength = 40;
        $m = $app->add(new ContainerAppMock(), 'quick-brown-fox');
        $m = $m->add(new ContainerAppMock(), 'jumps-over-a-lazy-dog');
        $m = $m->add(new ContainerAppMock(), 'then-they-go-out-for-a-pint');
        $m = $m->add(new ContainerAppMock(), 'eat-a-stake');
        $x = $m->add(new ContainerAppMock(), 'with');
        $x = $m->add(new ContainerAppMock(), 'a');
        $x = $m->add(new ContainerAppMock(), 'mint');

        static::assertSame(
            '_quick-brown-fox_jumps-over-a-lazy-dog_then-they-go-out-for-a-pint_eat-a-stake',
            $m->unshortenName($this)
        );

        static::assertLessThan(5, count($app->uniqueNameHashes));
        static::assertGreaterThan(2, count($app->uniqueNameHashes));

        $m->removeElement($x);

        static::assertSame(2, $m->getElementCount());
        $m->add(new \stdClass());

        static::assertSame(2, $m->getElementCount());
    }

    public function testLongNames2(): void
    {
        $app = new ContainerAppMock();
        $app->setApp($app);
        $app->maxNameLength = 40;
        $app->name = 'my-app-name-is-pretty-long';

        $minLength = 9999;
        $minLengthValue = '';
        $maxLength = 0;
        $maxLengthValue = '';

        for ($x = 1; $x < 100; ++$x) {
            $sh = str_repeat('x', $x);
            $m = $app->add(new ContainerAppMock(), $sh);
            if (strlen($m->name) > $maxLength) {
                $maxLength = strlen($m->name);
                $maxLengthValue = $m->name;
            }
            if (strlen($m->name) < $minLength) {
                $minLength = strlen($m->name);
                $minLengthValue = $m->name;
            }
        }

        // hash is 10 and we want 5 chars minimum for the right side e.g. XYXYXYXY__abcde
        static::assertGreaterThanOrEqual(15, $minLength);
        // hash is 10 and we want 5 chars minimum for the right side e.g. XYXYXYXY__abcde
        static::assertLessThanOrEqual($app->maxNameLength, $maxLength);
    }

    public function testPreservePresetNames(): void
    {
        $app = new ContainerAppMock();
        $app->setApp($app);
        $app->name = 'r';
        $app->maxNameLength = 40;

        $createTrackableMockFx = function (string $name, bool $isLongName = false) {
            return new class($name, $isLongName) extends TrackableMock {
                use Core\NameTrait;

                public function __construct(string $name, bool $isLongName)
                {
                    if ($isLongName) {
                        $this->name = $name;
                    } else {
                        $this->shortName = $name;
                    }
                }
            };
        };

        static::assertSame('r_foo', $app->add($createTrackableMockFx('foo'))->name);
        static::assertSame('r_bar', $app->add($createTrackableMockFx('bar'))->name);
        static::assertSame(40, strlen($app->add($createTrackableMockFx(str_repeat('x', 100)))->name));
        static::assertSame(40, strlen($app->add($createTrackableMockFx(str_repeat('x', 100)))->name));

        static::assertSame('foo', $app->add($createTrackableMockFx('foo', true))->name);

        $this->expectException(Core\Exception::class);
        static::assertSame(40, strlen($app->add($createTrackableMockFx(str_repeat('x', 100), true))->name));
    }

    public function testFactoryMock(): void
    {
        $m = new ContainerFactoryMock();
        $m2 = $m->add([ContainerMock::class]);
        static::assertSame(ContainerMock::class, get_class($m2));

        $m3 = $m->add([TrackableContainerMock::class], 'name');
        static::assertSame(TrackableContainerMock::class, get_class($m3));
        static::assertSame('name', $m3->shortName);
    }

    public function testArgs(): void
    {
        // passing name with array key 'name'
        $m = new ContainerMock();
        $m2 = $m->add(new class() extends TrackableMock {
            use Core\DiContainerTrait;
            use Core\NameTrait;
        }, ['name' => 'foo']);
        static::assertTrue($m->hasElement('foo'));
        static::assertSame('foo', $m2->shortName);
    }

    public function testExceptionExists(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new ContainerMock();
        $m->add(new TrackableMock(), 'foo');
        $m->add(new TrackableMock(), 'foo');
    }

    public function testDesiredName(): void
    {
        $m = new ContainerMock();
        $m->add(new TrackableMock(), ['desired_name' => 'foo']);
        $m->add(new TrackableMock(), ['desired_name' => 'foo']);

        static::assertTrue($m->hasElement('foo'));
    }

    public function testExceptionShortName(): void
    {
        $this->expectException(Core\Exception::class);
        $m1 = new ContainerMock();
        $m2 = new ContainerMock();
        $m1foo = $m1->add(new TrackableMock(), 'foo');
        $m2foo = $m2->add(new TrackableMock(), 'foo');

        // will carry on short name and run into collision.
        $m2->add($m1foo);
    }

    public function testExceptionArg2(): void
    {
        $m = new ContainerMock();

        if (\PHP_MAJOR_VERSION === 7) {
            $this->expectWarning();
        } else {
            $this->expectException(\TypeError::class);
        }
        $m->add(new TrackableMock(), 123); // @phpstan-ignore-line
    }

    public function testException3(): void
    {
        $m = new ContainerMock();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage(\PHP_MAJOR_VERSION < 8 ? 'Class \'hello\' not found' : 'Class "hello" not found');
        $m->add(['hello']);
    }

    public function testException4(): void
    {
        $m = new ContainerMock();

        $this->expectException(Core\Exception::class);
        $m->getElement('dont_exist');
    }

    public function testException5(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new ContainerMock();
        $m->removeElement('dont_exist');
    }
}

class TrackableMock
{
    use Core\TrackableTrait;
}
class ContainerFactoryMock
{
    use Core\ContainerTrait;
    use Core\NameTrait;
}

class TrackableContainerMock
{
    use Core\ContainerTrait;
    use Core\TrackableTrait;
}

class ContainerAppMock
{
    use Core\AppScopeTrait;
    use Core\ContainerTrait;
    use Core\NameTrait;
    use Core\TrackableTrait;

    public function getElementCount(): int
    {
        return count($this->elements);
    }

    public function unshortenName(): string
    {
        $n = $this->name;

        $d = array_flip($this->getApp()->uniqueNameHashes);

        for ($x = 0; str_contains($n, '__') && $x < 100; ++$x) {
            [$l, $r] = explode('__', $n);
            $l = $d[$l];
            $n = $l . $r;
        }

        return $n;
    }
}
