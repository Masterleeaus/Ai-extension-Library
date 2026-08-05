from __future__ import annotations

import json
import subprocess
import unittest
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[1]
COMMERCIAL = REPO_ROOT / "packages/workcore-commercial"
SHARED = REPO_ROOT / "packages/workcore-shared-foundation"
PRICING = COMMERCIAL / "src/Domains/WorkCore/System/Modules/Finance/Pricing"
FINANCE_PROVIDER = COMMERCIAL / "src/Domains/WorkCore/System/Modules/Finance/WorkCoreFinanceServiceProvider.php"
MIGRATION = SHARED / "src/Domains/WorkCore/Database/Migrations/2026_08_05_180000_create_workcore_dynamic_pricing_tables.php"


class WorkCoreDynamicPricingOwnershipTests(unittest.TestCase):
    def test_pricing_is_commercial_owned_and_migrations_are_parent_owned(self) -> None:
        self.assertTrue(PRICING.is_dir())
        self.assertTrue(MIGRATION.is_file())
        self.assertFalse((REPO_ROOT.parent.parent / "Domains/WorkCore/Pricing").exists())
        provider = FINANCE_PROVIDER.read_text(encoding="utf-8")
        self.assertIn("WorkPricingServiceProvider::class", provider)
        self.assertIn("$this->app->register(WorkPricingServiceProvider::class)", provider)

    def test_pricing_provider_uses_existing_finance_entitlement_and_registries(self) -> None:
        content = (PRICING / "WorkPricingServiceProvider.php").read_text(encoding="utf-8")
        for token in (
            "BusinessActionRegistry",
            "ReadModelRegistry",
            "PricingRepositoryContract::class",
            "DatabasePricingRepository::class",
            "workcore.pricing.rule.upsert",
            "workcore.pricing.signal.record",
            "workcore.pricing.apply",
            "workcore.pricing.preview",
            "workcore.pricing.analytics",
            "'workcore.finance'",
            "loadRoutesFrom",
            "loadViewsFrom",
        ):
            self.assertIn(token, content)


class WorkCoreDynamicPricingSecurityTests(unittest.TestCase):
    def test_repository_requires_company_scope_for_every_table_query(self) -> None:
        content = (PRICING / "Infrastructure/DatabasePricingRepository.php").read_text(encoding="utf-8")
        for table in (
            "tz_pricing_rules",
            "tz_seasonal_rates",
            "tz_demand_indicators",
            "tz_occupancy_snapshots",
            "tz_competitor_price_snapshots",
            "tz_price_history",
        ):
            self.assertIn(table, content)
        self.assertIn("private function assertCompanyId(int $companyId): void", content)
        self.assertGreaterEqual(content.count("where('company_id', $companyId)"), 6)
        self.assertNotIn("where('company_id', '!=',", content)

    def test_migration_creates_company_scoped_pricing_tables(self) -> None:
        content = MIGRATION.read_text(encoding="utf-8")
        for table in (
            "tz_pricing_rules",
            "tz_seasonal_rates",
            "tz_demand_indicators",
            "tz_occupancy_snapshots",
            "tz_competitor_price_snapshots",
            "tz_price_history",
        ):
            self.assertIn(f"Schema::create('{table}'", content)
        self.assertGreaterEqual(content.count("$table->unsignedBigInteger('company_id')->index();"), 6)
        self.assertIn("base_price_minor", content)
        self.assertIn("final_price_minor", content)
        self.assertIn("decision_hash", content)


class WorkCoreDynamicPricingSurfaceTests(unittest.TestCase):
    def test_api_and_dashboard_are_tenant_and_finance_gated(self) -> None:
        api = (PRICING / "routes/api.php").read_text(encoding="utf-8")
        user = (PRICING / "routes/user.php").read_text(encoding="utf-8")
        view = (PRICING / "resources/views/dashboard.blade.php").read_text(encoding="utf-8")
        for token in ("workcore.tenant", "workcore.api", "workcore.capability:workcore.finance"):
            self.assertIn(token, api)
        self.assertIn("workcore.workspace-capability:workcore.finance", user)
        self.assertIn("dashboard.user.workcore.commercial.pricing", user)
        self.assertIn("<x-layouts.app>", view)
        self.assertIn("pricing-impact", view)
        for legacy in ("jquery", "bootstrap", "DataTable"):
            self.assertNotIn(legacy, view)

    def test_controller_exposes_preview_apply_rules_signals_and_analytics(self) -> None:
        content = (PRICING / "Http/PricingController.php").read_text(encoding="utf-8")
        for method in (
            "public function preview(",
            "public function apply(",
            "public function upsertRule(",
            "public function recordSignal(",
            "public function analytics(",
            "public function dashboard(",
        ):
            self.assertIn(method, content)
        self.assertIn("BusinessActionExecutor", content)
        self.assertIn("ReadModelExecutor", content)
        self.assertIn("Idempotency-Key", content)


class WorkCoreDynamicPriceCalculatorTests(unittest.TestCase):
    def run_calculator(self, payload: dict[str, object]) -> dict[str, object]:
        calculator = PRICING / "Domain/DynamicPriceCalculator.php"
        decision = PRICING / "DTO/PricingDecision.php"
        php = f"""
        require {json.dumps(str(decision))};
        require {json.dumps(str(calculator))};
        $calculator = new App\\Domains\\WorkCore\\System\\Modules\\Finance\\Pricing\\Domain\\DynamicPriceCalculator();
        $decision = $calculator->calculate(json_decode({json.dumps(json.dumps(payload))}, true, 512, JSON_THROW_ON_ERROR));
        echo json_encode($decision->toArray(), JSON_THROW_ON_ERROR);
        """
        result = subprocess.run(["php", "-r", php], capture_output=True, text=True, check=False)
        self.assertEqual(0, result.returncode, result.stderr)
        return json.loads(result.stdout)

    def test_calculator_combines_priority_seasonal_occupancy_and_demand_with_bounds(self) -> None:
        result = self.run_calculator({
            "base_price_minor": 10000,
            "rules": [
                {"id": "later", "priority": 20, "type": "percentage", "value": 10},
                {"id": "first", "priority": 10, "type": "fixed_minor", "value": 500},
            ],
            "seasonal_multiplier": 1.20,
            "occupancy_percentage": 90,
            "demand_score": 80,
            "minimum_price_minor": 5000,
            "maximum_price_minor": 20000,
        })
        self.assertEqual(16632, result["final_price_minor"])
        self.assertEqual(["first", "later"], result["applied_rule_ids"])
        self.assertEqual("high", result["demand_level"])
        self.assertEqual(1.15, result["occupancy_multiplier"])
        self.assertEqual(1.10, result["demand_multiplier"])
        self.assertTrue(result["decision_hash"])

    def test_calculator_is_deterministic_and_clamps_low_prices(self) -> None:
        payload = {
            "base_price_minor": 10000,
            "rules": [{"id": "discount", "priority": 1, "type": "percentage", "value": -90}],
            "seasonal_multiplier": 0.5,
            "occupancy_percentage": 10,
            "demand_score": 0,
            "minimum_price_minor": 2500,
            "maximum_price_minor": 30000,
        }
        first = self.run_calculator(payload)
        second = self.run_calculator(payload)
        self.assertEqual(first, second)
        self.assertEqual(2500, first["final_price_minor"])
        self.assertTrue(first["minimum_bound_applied"])


if __name__ == "__main__":
    unittest.main()
