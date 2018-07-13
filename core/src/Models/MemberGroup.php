<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;

/**
 * EvolutionCMS\Models\MemberGroup
 *
 * @property int $id
 * @property int $user_group
 * @property int $member
 *
 * @mixin \Eloquent
 */
class MemberGroup extends Eloquent\Model
{
    use Traits\Models\ManagerActions;

	public $timestamps = false;

	protected $casts = [
		'user_group' => 'int',
		'member' => 'int'
	];

	protected $fillable = [
		'user_group',
		'member'
	];
}
