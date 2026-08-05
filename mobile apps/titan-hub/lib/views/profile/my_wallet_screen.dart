import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/routes/routes.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/others/custom_glass/custom_glass_widget.dart';
import 'package:qrpay/widgets/text_labels/title_heading2_widget.dart';

import '../../data/recent_transaction_data.dart';
import '../../language/english.dart';
import '../../utils/custom_color.dart';
import '../../utils/custom_style.dart';
import '../../utils/size.dart';
import '../../widgets/bottom_navbar/transaction_history_widget.dart';
import '../../widgets/text_labels/custom_title_heading_widget.dart';
import '../../widgets/text_labels/title_heading3_widget.dart';
import '../../widgets/text_labels/title_heading4_widget.dart';

class MyWalletScreen extends StatelessWidget {
  const MyWalletScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(
          text: Strings.myWallet,
        ),
        body: _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return Stack(
      children: [
        Column(
          children: [
            _amountWidget(context),
            _buttonWidget(context),
            const SizedBox()
          ],
        ),
        _draggableSheet(context),
      ],
    );
  }

  _draggableSheet(BuildContext context) {
    return DraggableScrollableSheet(
      builder: (_, scrollController) {
        return _transactionWidget(context, scrollController);
      },
      initialChildSize: 0.56,
      minChildSize: 0.56,
      maxChildSize: 1,
    );
  }

  _amountWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.8),
      height: MediaQuery.of(context).size.height * 0.25,
      decoration: BoxDecoration(
          color: CustomColor.primaryLightColor,
          borderRadius: BorderRadius.circular(Dimensions.radius * 1.5)),
      child: Column(
        mainAxisAlignment: mainCenter,
        children: [
          CustomTitleHeadingWidget(
            text: "541.44 USD",
            textAlign: TextAlign.center,
            style: CustomStyle.lightHeading1TextStyle.copyWith(
              fontSize: Dimensions.headingTextSize4 * 2,
              fontWeight: FontWeight.w800,
              color: CustomColor.whiteColor,
            ),
          ),
          CustomTitleHeadingWidget(
            text: Strings.currentBalance,
            textAlign: TextAlign.center,
            style: CustomStyle.darkHeading4TextStyle.copyWith(
                fontSize: Dimensions.headingTextSize3,
                fontWeight: FontWeight.w500,
                color: CustomColor.whiteColor.withOpacity(0.6)),
          ),
          verticalSpace(Dimensions.heightSize),
          //receive and send
          Padding(
            padding: EdgeInsets.only(
              top: Dimensions.marginSizeVertical,
            ),
            child: Row(
              mainAxisAlignment: mainCenter,
              children: [
                //recelive section
                Column(
                  mainAxisAlignment: mainCenter,
                  mainAxisSize: mainMin,
                  crossAxisAlignment: crossStart,
                  children: [
                    TitleHeading2Widget(
                      text: Strings.uSD541,
                      textAlign: TextAlign.center,
                      color: CustomColor.whiteColor.withOpacity(0.6),
                    ),
                    TitleHeading4Widget(
                      text: Strings.totalReceive,
                      textAlign: TextAlign.center,
                      color: CustomColor.whiteColor.withOpacity(0.4),
                    ),
                  ],
                ),
                Column(
                  mainAxisAlignment: mainCenter,
                  mainAxisSize: mainMin,
                  crossAxisAlignment: crossEnd,
                  children: [
                    TitleHeading2Widget(
                      text: Strings.uSD541,
                      textAlign: TextAlign.center,
                      color: CustomColor.whiteColor.withOpacity(0.6),
                    ),
                    TitleHeading4Widget(
                      text: Strings.totalSend,
                      textAlign: TextAlign.center,
                      color: CustomColor.whiteColor.withOpacity(0.4),
                    ),
                  ],
                )
              ],
            ),
          )
        ],
      ),
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeHorizontal * 0.7,
        horizontal: Dimensions.paddingSize * 0.8,
      ),
      child: Row(
        mainAxisAlignment: mainSpaceBet,
        children: [
          //!withdraw
          InkWell(
            onTap: () {
              Get.toNamed(Routes.withdrawScreen);
            },
            child: Container(
              alignment: Alignment.center,
              height: Dimensions.heightSize * 4,
              width: MediaQuery.of(context).size.width * 0.43,
              decoration: BoxDecoration(
                border:
                    Border.all(width: 3, color: CustomColor.primaryLightColor),
                borderRadius: BorderRadius.circular(
                  Dimensions.radius * 2.8,
                ),
              ),
              child: const TitleHeading3Widget(text: Strings.withdraw),
            ),
          ),
          horizontalSpace(Dimensions.widthSize * 0.4),
          //!deposti
          InkWell(
            onTap: () {
              Get.toNamed(Routes.addMoneyScreen);
            },
            child: Container(
              alignment: Alignment.center,
              height: Dimensions.heightSize * 4,
              width: MediaQuery.of(context).size.width * 0.43,
              decoration: BoxDecoration(
                color: CustomColor.primaryLightColor,
                borderRadius: BorderRadius.circular(
                  Dimensions.radius * 2.8,
                ),
              ),
              child: const TitleHeading3Widget(
                text: Strings.deposit,
                color: CustomColor.whiteColor,
              ),
            ),
          ),
        ],
      ),
    );
  }

  _transactionWidget(BuildContext context, ScrollController scrollController) {
    return ListView(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.8),
      physics: const NeverScrollableScrollPhysics(),
      children: [
        CustomTitleHeadingWidget(
          text: Strings.recentTransactions,
          style: Get.isDarkMode
              ? CustomStyle.darkHeading3TextStyle.copyWith(
                  fontSize: Dimensions.headingTextSize2,
                  fontWeight: FontWeight.w600,
                )
              : CustomStyle.lightHeading3TextStyle.copyWith(
                  fontSize: Dimensions.headingTextSize2,
                  fontWeight: FontWeight.w600,
                ),
        ),
        verticalSpace(Dimensions.widthSize),
        SizedBox(
          height: MediaQuery.of(context).size.height,
          child: ListView.builder(
              controller: scrollController,
              physics: const BouncingScrollPhysics(),
              itemCount: recentTransactionData.length,
              itemBuilder: (context, index) {
                return TransactionWidget(
                  amount: recentTransactionData[index].amount,
                  title: recentTransactionData[index].title,
                  dateText: recentTransactionData[index].dateText,
                  transaction: recentTransactionData[index].transaction,
                  monthText: recentTransactionData[index].monthText,
                );
              }),
        )
      ],
    ).customGlassWidget();
  }
}
