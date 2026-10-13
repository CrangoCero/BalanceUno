<?php

namespace App\Models;

use PDO;

class Report extends BaseModel
{
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'incomes');
    }

    public function getIncomes(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("SELECT * FROM incomes WHERE date BETWEEN ? AND ? AND company_id = ? AND deleted_at IS NULL");
        $stmt->execute([$startDate, $endDate, $this->getCompanyId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpenses(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("SELECT * FROM expenses WHERE date BETWEEN ? AND ? AND company_id = ? AND deleted_at IS NULL");
        $stmt->execute([$startDate, $endDate, $this->getCompanyId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotals(string $startDate, string $endDate): ?array
    {
        $companyId = $this->getCompanyId();
        $stmt = $this->db->prepare("SELECT 
                (SELECT COALESCE(SUM(amount),0) FROM incomes WHERE date BETWEEN ? AND ? AND company_id = ? AND deleted_at IS NULL) AS totalIncomes,
                (SELECT COALESCE(SUM(amount),0) FROM expenses WHERE date BETWEEN ? AND ? AND company_id = ? AND deleted_at IS NULL) AS totalExpenses
        ");
        $stmt->execute([$startDate, $endDate, $companyId, $startDate, $endDate, $companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getPaymentSummary(string $startDate, string $endDate): array
    {
        $companyId = $this->getCompanyId();
        $stmt = $this->db->prepare("SELECT payment_method, SUM(amount) as total, 'Ingreso' as tipo
            FROM incomes WHERE date BETWEEN ? AND ? AND company_id = ? AND deleted_at IS NULL GROUP BY payment_method
            UNION ALL
            SELECT payment_method, SUM(amount) as total, 'Gasto' as tipo
            FROM expenses WHERE date BETWEEN ? AND ? AND company_id = ? AND deleted_at IS NULL GROUP BY payment_method
        ");
        $stmt->execute([$startDate, $endDate, $companyId, $startDate, $endDate, $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
