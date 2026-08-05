import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/others/preview/amount_preview_widget.dart';
import 'package:qrpay/widgets/others/preview/information_amount_widget.dart';

import '../../../controller/categories/deposit/deposti_controller.dart';
import '../../../language/english.dart';
import '../../../utils/dimensions.dart';
import '../../../widgets/buttons/primary_button.dart';
import '../../../widgets/others/limit_information_widget.dart';

class DepositPreviewScreen extends StatelessWidget {
  DepositPreviewScreen({super.key});

  final controller = Get.put(DepositController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
        mobileScaffold: Scaffold(
      appBar: const AppBarWidget(text: Strings.preview),
      body: _bodyWidget(context),
    ));
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.8),
      physics: const BouncingScrollPhysics(),
      children: [
        _amountWidget(context),
        _amountInformationWidget(context),
        _limitInformation(context),
        _buttonWidget(context),
      ],
    );
  }

  _amountWidget(BuildContext context) {
    return previewAmount(amount: controller.enteredAmount);
  }

  _amountInformationWidget(BuildContext context) {
    return amountInformationWidget(
      information: Strings.amountInformation,
      enterAmount: Strings.enterAmount,
      enterAmountRow: controller.enteredAmount,
      fee: Strings.totalFee,
      feeRow: controller.transferFeeAmount,
      received: Strings.received,
      receivedRow: controller.youWillGet,
      total: Strings.totalPayable,
      totalRow: controller.payableAmount,
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 2,
      ),
      child: PrimaryButton(
          title: Strings.confirm,
          onPressed: () {
            if (controller.selectedCurrencyType.value.contains("AUTOMATIC")) {
              if (controller.selectedCurrencyAlias.contains('paypal')) {
                controller.goToWebPaymentViewScreen();
              } else if (controller.selectedCurrencyAlias
                  .contains('flutterwave')) {
                debugPrint("_______________2.2 flutterwave");
                controller.goToWebFlutterWavePaymentViewScreen();
              } else if (controller.selectedCurrencyAlias.contains('stripe')) {
                controller.goToStripeScreen();
              } else if (controller.selectedCurrencyAlias
                  .contains('razorpay')) {
                controller.goToRazorPayScreen();
              } else if (controller.selectedCurrencyAlias
                  .contains('pagadito')) {
                controller.goToPagaditoWebPaymentScreen();
              } else if (controller.selectedCurrencyAlias.contains('ssl')) {
                controller.goToSslScreen();
              } else if (controller.selectedCurrencyAlias
                  .contains('coingate')) {
                controller.goToCoinGateScreen();
              } else if (controller.selectedCurrencyAlias
                  .contains('perfect-money')) {
                controller.goToPerfectMoneyScreen();
              } else if (controller.selectedCurrencyAlias
                  .contains('paystack')) {
                controller.goToPayStackScreen();
              } else if (controller.selectedCurrencyAlias.contains('bkash')) {
                controller.goToBkashScreen();
              } else if (controller.selectedCurrencyAlias.contains('tatum')) {
                controller.goToTatumScreen();
              }
            } else if (controller.selectedCurrencyType.value
                .contains("MANUAL")) {
              controller.goToManualSendMoneyManualScreen();
            }
          }),
    );
  }

  _limitInformation(BuildContext context) {
    return LimitInformationWidget(
      showDailyLimit: controller.dailyLimit.value == 0.0 ? false : true,
      showMonthlyLimit: controller.monthlyLimit.value == 0.0 ? false : true,
      transactionLimit:
          '${controller.limitMin.value.toStringAsFixed(2)} - ${controller.limitMax.value.toStringAsFixed(2)} ${controller.baseCurrency.value}',
      dailyLimit:
          '${controller.dailyLimit.value.toStringAsFixed(2)} ${controller.baseCurrency.value}',
      monthlyLimit:
          '${controller.monthlyLimit.value} ${controller.baseCurrency.value}',
      remainingMonthLimit:
          '${controller.remainingController.remainingMonthLyLimit.value} ${controller.baseCurrency.value}',
      remainingDailyLimit:
          '${controller.remainingController.remainingDailyLimit.value} ${controller.baseCurrency.value}',
    );
  }
}
