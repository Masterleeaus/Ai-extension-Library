import 'package:intl/intl.dart';
import 'package:qrpay/controller/categories/request_money/request_money_logs_controller.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';

import '../../../backend/model/request_money/request_money_log_model.dart';
import '../../../backend/utils/custom_loading_api.dart';
import '../../../backend/utils/no_data_widget.dart';
import '../../../widgets/bottom_navbar/transaction_history_widget.dart';
import '../../../widgets/expended_item_widget.dart';

class RequestMoneyLogScreen extends StatelessWidget {
  RequestMoneyLogScreen({
    super.key,
  });
  final controller = Get.put(RequestMoneyLogsController());
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarWidget(text: Strings.requestMoneyLog),
      body: Obx(
        () {
          return controller.isLoading
              ? const CustomLoadingAPI()
              : SizedBox(
                  height: MediaQuery.of(context).size.height,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SizedBox(
                        height: Dimensions.heightSize * 1.5,
                      ),
                      Flexible(
                        child: controller.requestMoneyInfoModel.data
                                .transactions.isNotEmpty
                            ? RefreshIndicator(
                                color: CustomColor.primaryLightColor,
                                onRefresh: () async {
                                  controller.getRequestMoneyInfoProcessApi();
                                },
                                child: ListView.separated(
                                  physics: const BouncingScrollPhysics(),
                                  padding: EdgeInsets.symmetric(
                                    horizontal: Dimensions.paddingSize * .3,
                                  ),
                                  separatorBuilder: (_, index) =>
                                      verticalSpace(4),
                                  itemCount: controller.requestMoneyInfoModel
                                      .data.transactions.length,
                                  itemBuilder: (_, i) {
                                    return _mainListWidget(
                                      i,
                                      controller.requestMoneyInfoModel.data
                                          .transactions,
                                      context,
                                    );
                                  },
                                ),
                              )
                            : NoDataWidget(
                                title: Strings.noTransaction.tr,
                              ),
                      ),
                    ],
                  ),
                );
        },
      ),
    );
  }

  _mainListWidget(int i, List<Transaction> data, BuildContext context) {
    RxBool isExpansion = false.obs;
    return GestureDetector(
      onTap: () {
        isExpansion.value = !isExpansion.value;
      },
      child: Column(
        children: [
          TransactionWidget(
            status: data[i].status == 1
                ? 'success'
                : data[i].status == 2
                    ? 'Pending'
                    : 'Rejected',

            amount: data[i].requestAmount,
            payable: data[i].payable,
            title: data[i].requestType,
            dateText: DateFormat.d().format(data[i].createdAt),
            transaction: data[i].trx,
            monthText: DateFormat.MMM().format(data[i].createdAt),
            // requestMoney: true,
          ),
          Obx(
            () => Visibility(
              visible: isExpansion.value,
              child: Container(
                padding: EdgeInsets.all(Dimensions.paddingSize * .6),
                decoration: BoxDecoration(
                  color: CustomColor.primaryLightColor.withOpacity(0.9),
                  borderRadius: BorderRadius.circular(Dimensions.radius),
                ),
                child: Column(
                  children: [
                    ExpendedItemWidget(
                      title: Strings.transactionId.tr,
                      value: data[i].trx,
                    ),
                    ExpendedItemWidget(
                      title: Strings.requestType,
                      value: data[i].requestType,
                    ),
                    ExpendedItemWidget(
                      title: Strings.feesAndCharges.tr,
                      value: data[i].charge,
                    ),
                    ExpendedItemWidget(
                      title: Strings.totalPayable.tr,
                      value: data[i].payable,
                    ),
                    ExpendedItemWidget(
                      title: Strings.timeAndDate.tr,
                      value: DateFormat('yyyy-MM-dd').format(data[i].createdAt),
                    ),
                    verticalSpace(Dimensions.heightSize),
                    if (data[i].action) ...[
                      if (data[i].status != 1 && data[i].status != 4) ...[
                        Row(
                          mainAxisAlignment: mainEnd,
                          children: [
                            _customButtonWidget(
                              title: Strings.approve,
                              onTap: () {
                                controller.target.value = data[i].id.toString();
                                controller.logApproveProcessApi().then(
                                    (value) =>
                                        controller.getRequestMoneyLogData());
                              },
                              isLoading: controller.isApproveLoading,
                            ),
                            horizontalSpace(Dimensions.widthSize),
                            _customButtonWidget(
                              title: Strings.reject,
                              onTap: () {
                                controller.target.value = data[i].id.toString();
                                controller.logRejectProcessApi().then((value) =>
                                    controller.getRequestMoneyLogData());
                              },
                              isLoading: controller.isRejectLoading,
                            ),
                          ],
                        )
                      ]
                    ]
                  ],
                ),
              ),
            ),
          )
        ],
      ),
    );
  }

  _customButtonWidget({
    required String title,
    required Null Function() onTap,
    required bool isLoading,
  }) {
    return isLoading
        ? Container(
            margin: EdgeInsets.symmetric(
              horizontal: Dimensions.marginSizeHorizontal * 0.5,
            ),
            child: const CustomLoadingAPI(
              colors: CustomColor.whiteColor,
            ),
          )
        : InkWell(
            onTap: onTap,
            child: Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(Dimensions.radius),
                color: title == Strings.approve
                    ? CustomColor.greenColor
                    : CustomColor.redColor,
              ),
              padding: EdgeInsets.symmetric(
                horizontal: Dimensions.marginSizeHorizontal * 0.5,
                vertical: Dimensions.marginSizeVertical * 0.3,
              ),
              child: TitleHeading4Widget(
                text: title,
                color: CustomColor.whiteColor,
              ),
            ),
          );
  }
}
