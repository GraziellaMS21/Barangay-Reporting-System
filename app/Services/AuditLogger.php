<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Wrap the existing audit mechanism.
     */
    public function log(?User $by, string $action, Model|string $subject, array $old = [], array $new = []): void
    {
        $modelName = is_string($subject) ? $subject : get_class($subject);
        $modelId = ($subject instanceof Model) ? $subject->getKey() : null;

        AuditLog::create([
            'user_id' => $by?->id,
            'action' => $action,
            'model' => $modelName,
            'model_id' => $modelId,
            'old_values' => empty($old) ? null : $old,
            'new_values' => empty($new) ? null : $new,
        ]);
    }
}
