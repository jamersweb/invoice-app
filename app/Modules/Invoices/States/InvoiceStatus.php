<?php

namespace App\Modules\Invoices\States;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class InvoiceStatus extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, UnderReview::class)
            ->allowTransition(UnderReview::class, Offered::class)
            ->allowTransition(Offered::class, Accepted::class)
            ->allowTransition(Offered::class, Declined::class)
            ->allowTransition(Accepted::class, Funded::class)
            ->allowTransition(Funded::class, Settled::class)
            ->allowTransition(Funded::class, Overdue::class)
            ->allowTransition(Overdue::class, WrittenOff::class);
    }
}

class Draft extends InvoiceStatus { public static string $name = 'draft'; }
class UnderReview extends InvoiceStatus { public static string $name = 'under_review'; }
class Offered extends InvoiceStatus { public static string $name = 'offered'; }
class Accepted extends InvoiceStatus { public static string $name = 'accepted'; }
class Declined extends InvoiceStatus { public static string $name = 'declined'; }
class Funded extends InvoiceStatus { public static string $name = 'funded'; }
class Settled extends InvoiceStatus { public static string $name = 'settled'; }
class Overdue extends InvoiceStatus { public static string $name = 'overdue'; }
class WrittenOff extends InvoiceStatus { public static string $name = 'written_off'; }


