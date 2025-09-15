<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogActivity
{
    protected ?Model $subject = null;          // keep subject as a Model (e.g., Ticket, Violation)
    protected $actor = null;                    // Admin/Enforcer (Model or Authenticatable)
    protected ?string $event = null;
    protected array $properties = [];
    protected ?string $ip = null;
    protected ?string $userAgent = null;

    // Use this for model subjects like Ticket/Violation
    public static function on(Model $subject): self {
        $i = new self;
        $i->subject = $subject;
        return $i;
    }

    // Use this for login/logout where $user is Authenticatable
    public static function forUser(AuthenticatableContract $user): self {
        $i = new self;
        if ($user instanceof Model) {
            $i->subject = $user; // subject = the same user, so subject_type/id are filled
        }
        $i->actor = $user;
        return $i;
    }

    public function by($actor): self {
        $this->actor = $actor;
        return $this;
    }

    public function byCurrentUser(): self
    {
        $this->actor = Auth::guard('admin')->user() ?: Auth::guard('enforcer')->user();
        return $this;
    }

    public function event(string $event): self {
        $this->event = $event;
        return $this;
    }

    public function withProperties(array $props): self {
        $this->properties = $props;
        return $this;
    }

    public function fromRequest(?Request $request = null): self {
        $req = $request ?? request();
        $this->ip = $req->ip();
        $this->userAgent = substr($req->userAgent() ?? '', 0, 255);
        return $this;
    }

    public function log(string $description): ActivityLog {
        // Resolve actor id for Model or Authenticatable
        $actorType = $this->actor ? get_class($this->actor) : null;
        $actorId   = null;
        if ($this->actor instanceof Model) {
            $actorId = $this->actor->getKey();
        } elseif ($this->actor && method_exists($this->actor, 'getAuthIdentifier')) {
            $actorId = $this->actor->getAuthIdentifier();
        }

        return ActivityLog::create([
            'actor_type'   => $actorType,
            'actor_id'     => $actorId,
            'subject_type' => $this->subject ? get_class($this->subject) : null,
            'subject_id'   => $this->subject?->getKey(),
            'action'       => $this->event ?? 'custom',
            'description'  => $description,
            'properties'   => $this->properties ?: null,
            'ip'           => $this->ip,
            'user_agent'   => $this->userAgent,
        ]);
    }
}
