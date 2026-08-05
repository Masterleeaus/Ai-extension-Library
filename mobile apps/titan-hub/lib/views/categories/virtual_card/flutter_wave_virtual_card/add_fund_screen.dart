import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/routes/routes.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';

import '../../../../controller/categories/virtual_card/flutter_wave_virtual_card/adfund_controller.dart';
import '../../../../language/english.dart';
import '../../../../widgets/others/congratulation_widget.dart';
import '../../../../widgets/others/customInput_widget.dart/add_fund_keybaord.dart';

class AddFundScreen extends StatelessWidget {
  AddFundScreen({super.key, required this.appBarTitle});

  final String appBarTitle;
  final controller = Get.put(AddFundController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: AppBarWidget(text: appBarTitle),
        body: _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      physics: const BouncingScrollPhysics(),
      children: [
        AddFundCustomAmountWidget(
          buttonText: appBarTitle,
          onTap: () {
            debugPrint(appBarTitle);
            if (appBarTitle == Strings.addFund) {
              controller
                  .carAddFundProcess(controller.virtualCardController
                      .cardInfoModel.data.myCard.first.cardId)
                  .then((value) {
                if (context.mounted) {
                  StatusScreen.show(
                      context: context,
                      subTitle: Strings.addMoneySuccessfully.tr,
                      onPressed: () {
                        Get.offAllNamed(
                          Routes.bottomNavBarScreen,
                        );
                      });
                }
              });
            } else {
              controller.cardCreateProcess();
            }
          },
        ),
      ],
    );
  }
}
