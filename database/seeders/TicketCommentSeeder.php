<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketCommentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating ticket comments...');

        $agents = User::where('role', UserRole::AGENT)->get();
        $admin = User::where('role', UserRole::ADMIN)->first();
        $tickets = Ticket::all();


        $allCustomers = User::where('role', UserRole::CUSTOMER)->get();

        foreach ($tickets as $ticket) {

            $customer = $ticket->user ?? $ticket->customer;


            if (!$customer) {
                $customerId = $ticket->customer_id ?? $ticket->user_id;
                $customer = User::find($customerId);
            }


            if (!$customer) {
                $customer = $allCustomers->random();
            }

            $this->createCommentsForTicket($ticket, $customer, $agents, $admin);
        }

        $this->command->info('Ticket comments created successfully');
    }

    private function createCommentsForTicket(Ticket $ticket, User $customer, $agents, User $admin): void
    {
        $commentCount = fake()->numberBetween(2, 6);

        for ($i = 0; $i < $commentCount; $i++) {
            $author = $this->getCommentAuthor($customer, $agents, $admin, $i, $commentCount);
            $isInternal = ($author->isAgent() || $author->isAdmin()) && fake()->boolean(30);

            TicketComment::factory()
                ->for($ticket)
                ->for($author, 'user')
                ->state([
                    'is_internal' => $isInternal,
                    'body' => $this->generateRealisticCommentBody($author, $isInternal, $i),
                ])
                ->create();
        }
    }

    private function getCommentAuthor(User $customer, $agents, User $admin, int $commentIndex, int $totalComments): User
    {
        if ($commentIndex === 0) return $customer;
        if ($commentIndex === $totalComments - 1) return fake()->boolean(60) ? $agents->random() : $customer;

        $rand = fake()->numberBetween(1, 100);
        if ($rand <= 50) return $customer;
        elseif ($rand <= 95) return $agents->random();
        else return $admin;
    }

    private function generateRealisticCommentBody(User $author, bool $isInternal, int $commentIndex): string
    {
        if ($isInternal) {
            return fake()->randomElement([
                'Internal note: Customer seems frustrated. Need to prioritize this.',
                'Escalating to senior support team for review.',
                'Technical issue confirmed. Forwarding to development team.',
                'Customer has been contacted via phone. Issue resolved.',
                'Duplicate of ticket #' . fake()->numberBetween(1, 100) . '. Merging.',
            ]);
        }


        if (method_exists($author, 'isCustomer') ? $author->isCustomer() : $author->role === UserRole::CUSTOMER) {
            if ($commentIndex === 0) {
                return 'I am experiencing issues with ' . fake()->randomElement([
                    'login functionality', 'payment processing', 'data synchronization',
                    'email notifications', 'mobile app crashes'
                ]) . '. Please help resolve this as soon as possible.';
            } else {
                return fake()->randomElement([
                    'Thank you for the quick response. The issue is now resolved.',
                    'I tried the suggested solution but the problem persists.',
                    'Could you please provide more details on the next steps?',
                    'The issue seems to be intermittent. It works sometimes.',
                    'I have additional information that might help with the diagnosis.',
                ]);
            }
        } else {
            return fake()->randomElement([
                'Thank you for contacting support. I am looking into this issue.',
                'I have identified the root cause and am working on a solution.',
                'Please try clearing your browser cache and cookies, then retry.',
                'I have applied a fix on our end. Please test and confirm if the issue is resolved.',
                'This issue has been resolved. Please let us know if you need further assistance.',
                'I am escalating this to our technical team for further investigation.',
            ]);
        }
    }
}
