<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';
    protected $description = 'Mark pending invoices as overdue when past due date';

    public function handle(): int
    {
        $this->info('Checking for overdue invoices...');
        
        $count = DB::transaction(function () {
            $invoices = Invoice::query()
                ->where('status', Invoice::STATUS_PENDING)
                ->where('due_date', '<', now()->startOfDay())
                ->get();
            
            $markedCount = 0;
            
            foreach ($invoices as $invoice) {
                if ($invoice->markAsOverdue()) {
                    Transaction::create([
                        'user_id' => $invoice->user_id,
                        'invoice_id' => $invoice->id,
                        'transaction_type' => Transaction::TYPE_INVOICE_PAYMENT,
                        'event' => 'status_changed',
                        'result_desc' => 'Invoice marked as overdue',
                        'processed' => true,
                    ]);
                    
                    $markedCount++;
                    
                    Log::info('Invoice marked as overdue', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'due_date' => $invoice->due_date,
                    ]);
                }
            }
            
            return $markedCount;
        });
        
        $this->info("Marked {$count} invoice(s) as overdue.");
        
        return Command::SUCCESS;
    }
}