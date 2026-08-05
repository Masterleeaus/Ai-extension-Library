import 'package:flutter/material.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/responsive_layout.dart';

import '../../data/recent_transaction_data.dart';
import '../../language/english.dart';
import '../../widgets/appbar/appbar_widget.dart';
import '../../widgets/bottom_navbar/transaction_history_widget.dart';

class GiftCardLogScreen extends StatelessWidget {
  const GiftCardLogScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(
          text: Strings.giftcardLog,
        ),
        body: _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return ListView.builder(
        padding: EdgeInsets.symmetric(
          horizontal: Dimensions.marginSizeHorizontal * 0.9,
        ),
        physics: const BouncingScrollPhysics(),
        itemCount: giftCardData.length,
        itemBuilder: (context, index) {
          return TransactionWidget(
            amount: giftCardData[index].amount,
            title: giftCardData[index].title,
            dateText: giftCardData[index].dateText,
            transaction: giftCardData[index].transaction,
            monthText: giftCardData[index].monthText,
          );
        });
  }
}
