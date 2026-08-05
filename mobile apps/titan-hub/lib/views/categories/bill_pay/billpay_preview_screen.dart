import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/others/preview/amount_preview_widget.dart';
import 'package:qrpay/widgets/others/preview/billing_info.dart';
import 'package:qrpay/widgets/others/preview/billing_info_amount.dart';

import '../../../controller/categories/bill_pay/bill_pay_controller.dart';
import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../utils/dimensions.dart';
import '../../../widgets/buttons/primary_button.dart';
import '../../../widgets/others/congratulation_widget.dart';

class BillPayPreviewScreen extends StatelessWidget {
  BillPayPreviewScreen({super.key});
  final controller = Get.put(BillPayController());
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
        _recipientWidget(context),
        _amountInformationWidget(context),
        _buttonWidget(context),
      ],
    );
  }

  _amountWidget(BuildContext context) {
    return previewAmount(
        amount:
            "${controller.amountController.text} ${controller.baseCurrency.value}");
  }

  _recipientWidget(BuildContext context) {
    return billingInfo(
      recipient: Strings.billingInformation,
      billType: controller.billMethodselected.value,
      billNumber: controller.billNumberController.text,
      billMonth: controller.selectedBillMonths.value,
    );
  }

  _amountInformationWidget(BuildContext context) {
    return billingInformationWidget(
      enterAmount:
          "${controller.amountController.text} ${controller.automaticSelectedCurrency.value}",
      exchangeRate:
          '1 ${controller.automaticSelectedCurrency.value} ${controller.exchangeRate.value.toStringAsFixed(8)}',
      conversionAmount: (double.parse(controller.amountController.text) *
              controller.exchangeRate.value)
          .toStringAsFixed(4),
      totalCharge: '',
      totalPayable: '',
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
          StatusScreen.show(
            context: context,
            subTitle: Strings.yourBillHasBeenPaid.tr,
            onPressed: () {
              Get.toNamed(Routes.bottomNavBarScreen);
            },
          );
        },
      ),
    );
  }
}
