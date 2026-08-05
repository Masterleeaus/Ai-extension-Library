import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/others/preview/amount_preview_widget.dart';
import 'package:qrpay/widgets/others/preview/information_amount_widget.dart';
import 'package:qrpay/widgets/others/preview/recipient_preview_widget.dart';

import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../utils/dimensions.dart';
import '../../../widgets/buttons/primary_button.dart';
import '../../../widgets/others/congratulation_widget.dart';

class MobilePreviewScreen extends StatelessWidget {
  const MobilePreviewScreen({super.key});

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
    return previewAmount(amount: Strings.usd100);
  }

  _recipientWidget(BuildContext context) {
    return previewRecipient(
      recipient: Strings.topUpInformation,
      name: Strings.atAnd,
      nameRow: Strings.numberPlus1684,
      subTitle: Strings.mobileTopUpType,
      subTitleRow: Strings.mobileNumber,
    );
  }

  _amountInformationWidget(BuildContext context) {
    return amountInformationWidget(
      information: Strings.amountInformation,
      enterAmount: Strings.enterAmount,
      enterAmountRow: Strings.usd100,
      fee: Strings.transferFee,
      feeRow: Strings.uSD2,
      received: Strings.recipientReceived,
      receivedRow: Strings.uSD98,
      total: Strings.totalPayable,
      totalRow: Strings.usd102,
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
                subTitle: Strings.yourMobileTopUp.tr,
                onPressed: () {
                  Get.toNamed(Routes.bottomNavBarScreen);
                });
          }),
    );
  }
}
