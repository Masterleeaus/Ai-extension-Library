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
NATIVE = REPO_ROOT / "native-extensions/WorkCore"
WORKSPACES = NATIVE / "System/Navigation/workspaces.php"
WORKSPACE_CONTROLLER = NATIVE / "System/Http/Controllers/WorkspaceController.php"


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
            "PricingInputValidator::class",
            "workcore.pricing.rule.upsert",
            "workcore.pricing.seasonal.upsert",
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
        for token in (
            "$this->validator->validateRule($payload)",
            "$this->validator->validateSeasonalRate($payload)",
            "$this->validator->validateSignal($payload)",
            "$this->validator->validatePriceInput($input)",
            "private function assertActorId(int $actorId): void",
        ):
            self.assertIn(token, content)

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
        self.assertIn("$table->unsignedBigInteger('updated_by')", content)


class WorkCoreDynamicPricingSurfaceTests(unittest.TestCase):
    def test_api_and_dashboard_are_tenant_and_finance_gated(self) -> None:
        api = (PRICING / "routes/api.php").read_text(encoding="utf-8")
        user = PRICING / "routes/user.php"
        view = (PRICING / "resources/views/dashboard.blade.php").read_text(encoding="utf-8")
        for token in ("workcore.tenant", "workcore.api", "workcore.capability:workcore.finance"):
            self.assertIn(token, api)
        self.assertFalse(user.exists(), "Pricing must use the catalogue-driven native workspace route, not a duplicate route file.")
        workspaces = WORKSPACES.read_text(encoding="utf-8")
        workspace_controller = WORKSPACE_CONTROLLER.read_text(encoding="utf-8")
        self.assertIn("workcore_commercial_pricing", workspaces)
        self.assertIn("dashboard.user.workcore.commercial.pricing", workspaces)
        self.assertIn("commercial/pricing", workspaces)
        self.assertIn("workcore-pricing::dashboard", workspace_controller)
        self.assertIn("workcore.pricing.analytics", workspace_controller)
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
            "public function upsertSeasonalRate(",
            "public function recordSignal(",
            "public function analytics(",
        ):
            self.assertIn(method, content)
        self.assertIn("BusinessActionDispatcher", content)
        self.assertIn("ReadModelExecutor", content)
        self.assertIn("Idempotency-Key", content)
        for token in (
            "required_if:signal_type,demand",
            "required_if:signal_type,occupancy",
            "required_if:signal_type,competitor",
            "lte:capacity",
            "gte:minimum_price_minor",
            "alpha",
            "ValidationException::withMessages",
        ):
            self.assertIn(token, content)

    def test_repository_scopes_competitor_analytics_and_manages_seasonal_rates(self) -> None:
        repository = (PRICING / "Infrastructure/DatabasePricingRepository.php").read_text(encoding="utf-8")
        contract = (PRICING / "Contracts/PricingRepositoryContract.php").read_text(encoding="utf-8")
        routes = (PRICING / "routes/api.php").read_text(encoding="utf-8")
        self.assertIn("public function upsertSeasonalRate", contract)
        self.assertIn("public function upsertSeasonalRate", repository)
        self.assertIn("seasonal-rates", routes)
        self.assertGreaterEqual(repository.count("$competitors->where('target_type'"), 1)
        self.assertGreaterEqual(repository.count("$competitors->where('target_reference'"), 1)
        self.assertIn("'occupancy_percentage' => $occupancy === null ? null", repository)
        pricing_service = (PRICING / "Services/PricingService.php").read_text(encoding="utf-8")
        self.assertIn("$this->validator->validatePriceInput($input)", pricing_service)


class WorkCoreDynamicPricingDomainValidationTests(unittest.TestCase):
    def run_validator(self, method: str, payload: dict[str, object]) -> tuple[int, str]:
        validator = PRICING / "Domain/PricingInputValidator.php"
        php = f"""
        require {json.dumps(str(validator))};
        $validator = new App\\Domains\\WorkCore\\System\\Modules\\Finance\\Pricing\\Domain\\PricingInputValidator();
        try {{
            $validator->{method}(json_decode({json.dumps(json.dumps(payload))}, true, 512, JSON_THROW_ON_ERROR));
            echo 'OK';
        }} catch (InvalidArgumentException $exception) {{
            fwrite(STDERR, $exception->getMessage());
            exit(3);
        }}
        """
        result = subprocess.run(["php", "-r", php], capture_output=True, text=True, check=False)
        return result.returncode, result.stderr

    def test_rule_validation_rejects_invalid_values_and_date_windows(self) -> None:
        invalid = [
            {"name": "Rule", "adjustment_type": "fixed_minor", "adjustment_value": 1.5},
            {"name": "Rule", "adjustment_type": "percentage", "adjustment_value": -100.1},
            {"name": "Rule", "adjustment_type": "multiplier", "adjustment_value": 0.09},
            {"name": "Rule", "adjustment_type": "percentage", "adjustment_value": 10, "starts_at": "2026-08-10 00:00:00", "ends_at": "2026-08-01 00:00:00"},
        ]
        for payload in invalid:
            code, error = self.run_validator("validateRule", payload)
            self.assertEqual(3, code, (payload, error))

    def test_seasonal_validation_rejects_invalid_multiplier_and_date_order(self) -> None:
        invalid = [
            {"name": "Peak", "starts_on": "2026-08-01", "ends_on": "2026-08-10", "multiplier": 10.1},
            {"name": "Peak", "starts_on": "2026-08-10", "ends_on": "2026-08-01", "multiplier": 1.2},
            {"name": "Peak", "starts_on": "not-a-date", "ends_on": "2026-08-10", "multiplier": 1.2},
        ]
        for payload in invalid:
            code, error = self.run_validator("validateSeasonalRate", payload)
            self.assertEqual(3, code, (payload, error))

    def test_signal_validation_rejects_missing_or_out_of_range_type_payloads(self) -> None:
        invalid = [
            {"signal_type": "demand", "target_type": "service", "target_reference": "alpha"},
            {"signal_type": "demand", "target_type": "service", "target_reference": "alpha", "score": 101},
            {"signal_type": "occupancy", "target_type": "service", "target_reference": "alpha", "capacity": 10, "occupied": 11},
            {"signal_type": "competitor", "target_type": "service", "target_reference": "alpha", "competitor_name": "", "observed_price_minor": 1000, "currency": "AUD"},
            {"signal_type": "competitor", "target_type": "service", "target_reference": "alpha", "competitor_name": "Other", "observed_price_minor": -1, "currency": "AUD"},
            {"signal_type": "competitor", "target_type": "service", "target_reference": "alpha", "competitor_name": "Other", "observed_price_minor": 1000, "currency": "A1D"},
        ]
        for payload in invalid:
            code, error = self.run_validator("validateSignal", payload)
            self.assertEqual(3, code, (payload, error))

    def test_price_input_validation_requires_target_and_valid_bounds(self) -> None:
        invalid = [
            {"base_price_minor": 1000, "currency": "AUD", "target_type": "", "target_reference": "alpha"},
            {"base_price_minor": 1000, "currency": "AUD", "target_type": "service", "target_reference": ""},
            {"base_price_minor": 1000, "currency": "AUD", "target_type": "service", "target_reference": "alpha", "minimum_price_minor": 2000, "maximum_price_minor": 1000},
        ]
        for payload in invalid:
            code, error = self.run_validator("validatePriceInput", payload)
            self.assertEqual(3, code, (payload, error))



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
        self.assertEqual(17533, result["final_price_minor"])
        self.assertEqual(["first", "later"], result["applied_rule_ids"])
        self.assertEqual("high", result["demand_level"])
        self.assertEqual(1.15, result["occupancy_multiplier"])
        self.assertEqual(1.10, result["demand_multiplier"])
        self.assertTrue(result["decision_hash"])


    def test_missing_occupancy_is_neutral_instead_of_discounted(self) -> None:
        result = self.run_calculator({
            "base_price_minor": 10000,
            "rules": [],
            "seasonal_multiplier": 1.0,
            "occupancy_percentage": None,
            "demand_score": 50,
            "minimum_price_minor": 0,
            "maximum_price_minor": 20000,
        })
        self.assertEqual(10000, result["final_price_minor"])
        self.assertEqual(1.0, result["occupancy_multiplier"])
        self.assertIsNone(result["factors"]["occupancy_percentage"])

    def test_occupancy_conditioned_rule_does_not_match_when_occupancy_is_unknown(self) -> None:
        result = self.run_calculator({
            "base_price_minor": 10000,
            "rules": [{
                "id": "low-occupancy-discount",
                "priority": 1,
                "type": "percentage",
                "value": -20,
                "conditions": {"occupancy_percentage_max": 30},
            }],
            "seasonal_multiplier": 1.0,
            "occupancy_percentage": None,
            "demand_score": 50,
        })
        self.assertEqual(10000, result["final_price_minor"])
        self.assertEqual([], result["applied_rule_ids"])

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
