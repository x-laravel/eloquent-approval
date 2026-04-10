<?php

namespace XLaravel\EloquentApproval\Tests;

use XLaravel\EloquentApproval\Tests\Models\Entity;
use XLaravel\EloquentApproval\ApprovalStatuses;
use PHPUnit\Framework\Attributes\Test;

/**
 *
 */
class SuspensionOnUpdateTest extends TestCase
{

	#[Test]
	public function it_works_when_all_attributes_require_approval_on_update()
	{
		$attributes = Entity::factory()->approved()->raw();

		with($entity = new class ($attributes) extends Entity {
			protected $table = 'entities';

			public function approvalRequired(): array
			{
				return ['*'];
			}

			public function approvalNotRequired(): array
			{
				return [];
			}
		})->save();

		$entity->update([
			'attr_1' => $attributes['attr_1'].'_changed',
		]);

		$this->assertEquals(ApprovalStatuses::PENDING, $entity->approval_status);
		$this->assertNull($entity->approval_at);

		$this->assertDatabaseHas('entities', [
			'id' => $entity->id,
			'approval_status' => ApprovalStatuses::PENDING,
			'approval_at' => null,
		]);
	}

	#[Test]
	public function it_works_when_some_attributes_do_not_require_approval_on_update()
	{
		$attributes = Entity::factory()->approved()->raw();

		// it isn't suspended on update of the attributes that don't require approval
		with($entity = new class ($attributes) extends Entity {
			protected $table = 'entities';

			public function approvalRequired(): array
			{
				return ['*'];
			}

			public function approvalNotRequired(): array
			{
				return ['attr_1',];
			}
		})->save();

		$entity->update([
			'attr_1' => $attributes['attr_1'].'_changed',
		]);

		$this->assertEquals(ApprovalStatuses::APPROVED, $entity->approval_status);
		$this->assertNotNull($entity->approval_at);

		$this->assertDatabaseHas('entities', [
			'id' => $entity->id,
			'approval_status' => ApprovalStatuses::APPROVED,
			'approval_at' => $attributes['approval_at']
		]);

		// it is suspended on update of the attributes that require approval
		with($entity = new class ($attributes) extends Entity {
			protected $table = 'entities';

			public function approvalRequired(): array
			{
				return ['*'];
			}

			public function approvalNotRequired(): array
			{
				return ['attr_1',];
			}
		})->save();

		$entity->update([
			'attr_2' => $attributes['attr_2'].'_changed',
		]);

		$this->assertEquals(ApprovalStatuses::PENDING, $entity->approval_status);
		$this->assertNull($entity->approval_at);

		$this->assertDatabaseHas('entities', [
			'id' => $entity->id,
			'approval_status' => ApprovalStatuses::PENDING,
			'approval_at' => null,
		]);
	}

	#[Test]
	public function it_works_when_some_attributes_require_approval_on_update()
	{
		$attributes = Entity::factory()->approved()->raw();

		// it isn't suspended on update of the attributes that don't require approval
		with($entity = new class ($attributes) extends Entity {
			protected $table = 'entities';

			public function approvalRequired(): array
			{
				return ['attr_1',];
			}

			public function approvalNotRequired(): array
			{
				return [];
			}
		})->save();

		$entity->update([
			'attr_2' => $attributes['attr_2'].'_changed',
			'attr_3' => $attributes['attr_3'].'_changed',
		]);

		$this->assertEquals(ApprovalStatuses::APPROVED, $entity->approval_status);
		$this->assertNotNull($entity->approval_at);

		$this->assertDatabaseHas('entities', [
			'id' => $entity->id,
			'approval_status' => ApprovalStatuses::APPROVED,
			'approval_at' => $attributes['approval_at']
		]);

		// it is suspended on update of the attributes that require approval
		with($entity = new class ($attributes) extends Entity {
			protected $table = 'entities';

			public function approvalRequired(): array
			{
				return ['attr_1',];
			}

			public function approvalNotRequired(): array
			{
				return [];
			}
		})->save();

		$entity->update([
			'attr_1' => $attributes['attr_1'].'_changed',
		]);

		$this->assertEquals(ApprovalStatuses::PENDING, $entity->approval_status);
		$this->assertNull($entity->approval_at);

		$this->assertDatabaseHas('entities', [
			'id' => $entity->id,
			'approval_status' => ApprovalStatuses::PENDING,
			'approval_at' => null,
		]);
	}

	#[Test]
	public function it_works_when_no_attribute_requires_approval_on_update()
	{
		$attributes = Entity::factory()->approved()->raw();

		with($entity = new class ($attributes) extends Entity {
			protected $table = 'entities';

			public function approvalRequired(): array
			{
				return [];
			}

			public function approvalNotRequired(): array
			{
				return [];
			}
		})->save();

		$entity->update([
			'attr_1' => $attributes['attr_1'].'_changed',
			'attr_2' => $attributes['attr_2'].'_changed',
			'attr_3' => $attributes['attr_3'].'_changed',
		]);

		$this->assertEquals(ApprovalStatuses::APPROVED, $entity->approval_status);
		$this->assertNotNull($entity->approval_status);

		$this->assertDatabaseHas('entities', [
			'id' => $entity->id,
			'approval_status' => ApprovalStatuses::APPROVED,
			'approval_at' => $attributes['approval_at']
		]);
	}
}