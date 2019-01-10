<?php
declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://hyperf.org
 * @document https://wiki.hyperf.org
 * @contact  group@hyperf.org
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Database\Model\Concerns;

use Hyperf\Database\Model\Events\Event;
use Hyperf\Utils\Str;
use Psr\EventDispatcher\EventDispatcherInterface;

trait HasEvents
{
    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Model events.
     *
     * @var array
     */
    protected $dispatchesEvents = [];

    /**
     * Fire the given event for the model.
     *
     * @param  string $event
     * @param  bool $halt
     * @return mixed
     */
    protected function fireModelEvent(string $event)
    {
        $dispatcher = $this->getEventDispatcher();
        if (!$dispatcher instanceof EventDispatcherInterface) {
            return true;
        }

        $result = $this->filterModelEventResults(
            $this->fireCustomModelEvent($event, 'dispatch')
        );

        if ($result === false) {
            return false;
        }

        $eventName = 'Hyperf\\Database\\Model\\Events\\' . Str::studly($event);
        return !empty($result) ? $result : $dispatcher->dispatch(new $eventName($this, $event));
    }

    /**
     * Fire a custom model event for the given event.
     *
     * @param  string $event
     * @param  string $method
     * @return mixed|null
     */
    protected function fireCustomModelEvent($event, $method)
    {
        if (!isset($this->dispatchesEvents[$event])) {
            return;
        }

        $result = static::$dispatcher->$method(new $this->dispatchesEvents[$event]($this));

        if (!is_null($result)) {
            return $result;
        }
    }

    /**
     * Filter the model event results.
     *
     * @param  mixed $result
     * @return mixed
     */
    protected function filterModelEventResults($result)
    {
        if (is_array($result)) {
            $result = array_filter($result, function ($response) {
                return !is_null($response);
            });
        }

        return $result;
    }
}
