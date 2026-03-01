<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ServiceRequest;
use App\Models\WhatsappInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;

    public function definition(): array
    {
        return [
            'company_id'            => Company::factory(),
            'whatsapp_instance_id'  => null,
            'attendant_id'          => null,
            'contact_name'          => $this->faker->name(),
            'contact_phone'         => '+55119' . $this->faker->numerify('########'),
            'contact_message'       => $this->faker->sentence(),
            'status'                => ServiceRequest::STATUS_PENDING,
            'urgency_level'         => ServiceRequest::URGENCY_NORMAL,
            'urgency_keywords'      => null,
            'channel'               => ServiceRequest::CHANNEL_WHATSAPP,
            'collected_data'        => null,
            'api_response'          => null,
            'api_sent_at'           => null,
            'api_attempts'          => 0,
            'external_ticket_id'    => null,
            'attended_at'           => null,
            'resolved_at'           => null,
            'notes'                 => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => ServiceRequest::STATUS_PENDING]);
    }

    public function aiCollecting(): static
    {
        return $this->state(['status' => ServiceRequest::STATUS_AI_COLLECTING]);
    }

    public function awaitingReview(): static
    {
        return $this->state(['status' => ServiceRequest::STATUS_AWAITING_REVIEW]);
    }

    public function sentToApi(): static
    {
        return $this->state(['status' => ServiceRequest::STATUS_SENT_API]);
    }

    public function resolved(): static
    {
        return $this->state([
            'status'      => ServiceRequest::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(['urgency_level' => ServiceRequest::URGENCY_URGENT]);
    }

    public function critical(): static
    {
        return $this->state(['urgency_level' => ServiceRequest::URGENCY_CRITICAL]);
    }
}
