<?php

namespace XLaravel\EloquentApproval\Tests;

use Illuminate\Support\Arr;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use XLaravel\EloquentApproval\ApprovalStatuses;
use XLaravel\EloquentApproval\Tests\Models\Entity;
use PHPUnit\Framework\Attributes\Test;

class ApprovalEventsTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    protected $actions = [
        'approve',
        'suspend',
        'reject'
    ];

    protected $statuses = [
        ApprovalStatuses::APPROVED,
        ApprovalStatuses::PENDING,
        ApprovalStatuses::REJECTED
    ];

    protected $checks = [
        'isApproved',
        'isPending',
        'isRejected'
    ];

    protected $beforeEvents = [
        'approving',
        'suspending',
        'rejecting'
    ];

    protected $afterEvents = [
        'approved',
        'suspended',
        'rejected'
    ];

    #[Test]
    public function it_dispatches_events_before_approval_actions()
    {
        $entity = Entity::factory()->create();

        for ($i = 0; $i < count($this->actions); $i++) {
            $action = $this->actions[$i];
            $event = $this->beforeEvents[$i];
            $listener = $event . 'Listener';

            $mock = Mockery::mock();
            $mock->shouldReceive($listener)->once();

            Entity::$event([$mock, $listener]);

            $entity->$action();
        }
    }

    #[Test]
    public function it_allows_listeners_of_before_action_events_halt_the_action_execution()
    {
        for ($i = 0; $i < count($this->actions); $i++) {
            $action = $this->actions[$i];
            $beforeEvent = $this->beforeEvents[$i];
            $beforeListener = $beforeEvent . 'Listener';
            $afterEvent = $this->afterEvents[$i];
            $afterEventListener = $afterEvent . 'Listener';

            $mock = Mockery::mock()->shouldIgnoreMissing();
            $mock->shouldReceive($beforeListener)->once()->andReturn(false);
            $mock->shouldNotReceive($afterEventListener);

            Entity::$beforeEvent([$mock, $beforeListener]);
            Entity::$afterEvent([$mock, $afterEventListener]);

            $entity = Entity::factory()->create([
                'approval_status' => Arr::random(Arr::except($this->statuses, [$i]))
            ]);

            $this->assertFalse($entity->$action());

            $this->assertFalse($entity->{$this->checks[$i]}());

            $this->assertDatabaseMissing('entities', [
                'id' => $entity->id,
                'approval_status' => $this->statuses[$i]
            ]);
        }
    }

    #[Test]
    public function it_dispatches_events_after_approval_actions()
    {
        $entity = Entity::factory()->create();

        for ($i = 0; $i < count($this->actions); $i++) {
            $action = $this->actions[$i];
            $event = $this->afterEvents[$i];
            $listener = $event . 'Listener';

            $mock = Mockery::mock();
            $mock->shouldReceive($listener)->once();
            $mock->shouldReceive('approvalChangedListener')->once();

            Entity::$event([$mock, $listener]);
            Entity::getEventDispatcher()->forget("eloquent.approvalChanged: " . Entity::class);
            Entity::approvalChanged([$mock, 'approvalChangedListener']);

            $entity->$action();
        }
    }

    #[Test]
    public function it_will_not_dispatch_the_events_on_the_duplicate_approvals()
    {
        for ($i = 0; $i < count($this->statuses); $i++) {
            $entity = Entity::factory()->create([
                'approval_status' => $this->statuses[$i],
                'approval_at' => (new Entity())->freshTimestamp()
            ]);

            $beforeEvent = $this->beforeEvents[$i];
            $afterEvent = $this->afterEvents[$i];

            $mock = Mockery::mock();
            $mock->shouldNotReceive('beforeListener');
            $mock->shouldNotReceive('afterListener');
            $mock->shouldNotReceive('approvalChangedListener');

            Entity::$beforeEvent([$mock, 'beforeListener']);
            Entity::$afterEvent([$mock, 'afterListener']);
            Entity::approvalChanged([$mock, 'approvalChangedListener']);

            $entity->{$this->actions[$i]}();
        }
    }

    #[Test]
    public function it_supports_observers()
    {
        $calls = [];

        $observer = new class($calls) {
            public function __construct(private array &$calls) {}

            public function approving($model): void { $this->calls[] = 'approving'; }
            public function approved($model): void { $this->calls[] = 'approved'; }
            public function suspending($model): void { $this->calls[] = 'suspending'; }
            public function suspended($model): void { $this->calls[] = 'suspended'; }
            public function rejecting($model): void { $this->calls[] = 'rejecting'; }
            public function rejected($model): void { $this->calls[] = 'rejected'; }
        };

        app()->instance(get_class($observer), $observer);

        Entity::observe($observer);

        $entity = Entity::factory()->create();

        foreach ($this->actions as $action) {
            $entity->$action();
        }

        $events = array_merge($this->beforeEvents, $this->afterEvents);
        $callCounts = array_count_values($calls);
        foreach ($events as $event) {
            $this->assertSame(1, $callCounts[$event] ?? 0, "Event '{$event}' should have been called once.");
        }
    }
}
