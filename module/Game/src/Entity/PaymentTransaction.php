<?php

namespace Game\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="payment_transaction")
 */
class PaymentTransaction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Game\\Entity\\GameRegister")
     * @ORM\JoinColumn(name="register_id", referencedColumnName="idregister", nullable=false, onDelete="CASCADE")
     */
    private $register;

    /** @ORM\Column(type="string", length=64, nullable=true) */
    private $provider_transaction_id;

    /** @ORM\Column(type="string", length=32) */
    private $status = 'paid';

    /** @ORM\Column(type="decimal", precision=10, scale=2) */
    private $refunded_amount = 0.00;

    /** @ORM\Column(type="datetime", nullable=true) */
    private $refund_done_at;

    public function getId()
    {
        return $this->id;
    }

    public function getRegister()
    {
        return $this->register;
    }

    public function setRegister($register)
    {
        $this->register = $register;
        return $this;
    }

    public function getProviderTransactionId()
    {
        return $this->provider_transaction_id;
    }

    public function setProviderTransactionId($provider_transaction_id)
    {
        $this->provider_transaction_id = $provider_transaction_id;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getRefundedAmount()
    {
        return $this->refunded_amount;
    }

    public function setRefundedAmount($refunded_amount)
    {
        $this->refunded_amount = $refunded_amount;
        return $this;
    }

    public function getRefundDoneAt()
    {
        return $this->refund_done_at;
    }

    public function setRefundDoneAt($refund_done_at)
    {
        $this->refund_done_at = $refund_done_at;
        return $this;
    }

}
