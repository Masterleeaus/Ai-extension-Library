import 'package:intl/intl.dart';

import '../../../../backend/utils/custom_loading_api.dart';
import '../../../../controller/categories/virtual_card/strowallet_card/strowallet_transaction_controller.dart';
import '../../../../utils/basic_screen_imports.dart';
import '../../../../utils/responsive_layout.dart';
import '../../../../widgets/appbar/appbar_widget.dart';
import '../../../../widgets/bottom_navbar/transaction_history_widget.dart';

class StrowalletTransactionHistoryScreen extends StatelessWidget {
  StrowalletTransactionHistoryScreen({super.key});
  final controller = Get.put(StrowalletTransactionController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.transactionHistory),
        body: Obx(
          () => controller.isLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    var data = controller.strowalletCardTransactionsModel.data.cardTransactions;

    return data.isNotEmpty
        ? RefreshIndicator(
            color: CustomColor.primaryLightColor,
            onRefresh: () async {
              controller.getCardTransactionHistory();
            },
            child: ListView.builder(
                padding: EdgeInsets.symmetric(
                  horizontal: Dimensions.marginSizeHorizontal * 0.9,
                ),
                itemCount: data.length,
                itemBuilder: (context, index) {
                  return TransactionWidget(
                    amount: data[index].amount,
                    title: '${'Trx'} ${data[index].cardId}',
                    dateText: DateFormat.M().format(data[index].createdAt),
                    transaction: data[index].status,
                    monthText: DateFormat.MMM().format(data[index].createdAt),
                  );
                }),
          )
        : Center(
            child: TitleHeading1Widget(
              text: Strings.noRecordFound,
              color: CustomColor.primaryLightColor,
            ),
          );
  }
}
