import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:intl/intl.dart';

import '../../../backend/model/transaction_log/transaction_log_model.dart';
import '../../../backend/utils/custom_loading_api.dart';
import '../../../backend/utils/no_data_widget.dart';
import '../../../controller/drawer/transaction_controller.dart';
import '../../../language/english.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/size.dart';
import '../../../widgets/bottom_navbar/transaction_history_widget.dart';
import '../../../widgets/buttons/primary_button.dart';
import '../../../widgets/expended_item_widget.dart';
import '../../../widgets/inputs/primary_input_filed.dart';

class AddMoneyLogScreen extends StatelessWidget {
  const AddMoneyLogScreen({super.key, required this.controller});
  final TransactionController controller;

  @override
  Widget build(BuildContext context) {
    var data = controller.transactioData.data.transactions.addMoney;
    return SizedBox(
      height: MediaQuery.of(context).size.height,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: Dimensions.heightSize * 1.5,
          ),
          SizedBox(
            height: MediaQuery.of(context).size.height * 0.78,
            child: data.isNotEmpty
                ? ListView.separated(
                    physics: const BouncingScrollPhysics(),
                    padding: EdgeInsets.symmetric(
                      horizontal: Dimensions.paddingSize * .3,
                    ),
                    separatorBuilder: (_, index) => verticalSpace(4),
                    itemCount: data.length,
                    itemBuilder: (_, i) {
                      return _mainListWidget(i, data[i], context);
                    },
                  )
                : NoDataWidget(
                    title: Strings.noTransaction.tr,
                  ),
          ),
        ],
      ),
    );
  }

  _mainListWidget(int i, data, BuildContext context) {
    RxBool isExpansion = false.obs;
    return Column(
      children: [
        InkWell(
          onTap: () {
            isExpansion.value = !isExpansion.value;
            if (isExpansion.value == true) {
              controller.inputFieldControllers.clear();
              controller.inputFields.clear();
              final metaData = data.dynamicInputs;
              for (int item = 0; item < metaData.length; item++) {
                var textEditingController = TextEditingController();
                controller.inputFieldControllers.add(textEditingController);
                controller.inputFields.add(
                  Column(
                    children: [
                      PrimaryInputWidget(
                        controller: controller.inputFieldControllers[item],
                        label: metaData[item].label,
                        hint: metaData[item].label,
                        isValidator: metaData[item].required,
                        isTrxHash: true,
                        inputFormatters: [
                          LengthLimitingTextInputFormatter(
                            int.parse(metaData[item].validation.max.toString()),
                          ),
                        ],
                      ),
                      verticalSpace(Dimensions.heightSize),
                    ],
                  ),
                );
              }
            } else {
              controller.inputFieldControllers.clear();
              controller.inputFields.clear();
            }
          },
          child: TransactionWidget(
            status: data.status,
            amount: data.requestAmount,
            title: data.transactionType,
            dateText: DateFormat.d().format(data.dateTime),
            transaction: data.trx,
            monthText: DateFormat.MMM().format(data.dateTime),
          ),
        ),
        Obx(() => Visibility(
              visible: isExpansion.value,
              child: Container(
                padding: EdgeInsets.all(Dimensions.paddingSize * .6),
                decoration: BoxDecoration(
                  color: CustomColor.primaryLightColor.withOpacity(0.9),
                  borderRadius: BorderRadius.circular(Dimensions.radius),
                ),
                child: ListView(
                  shrinkWrap: true,
                  children: [
                    ExpendedItemWidget(
                      title: Strings.transactionId.tr,
                      value: data.trx,
                    ),
                    ExpendedItemWidget(
                      title: Strings.exchangeRate.tr,
                      value: data.exchangeRate,
                    ),
                    ExpendedItemWidget(
                      title: Strings.feesAndCharges.tr,
                      value: data.totalCharge,
                    ),
                    ExpendedItemWidget(
                      title: Strings.currentBalance.tr,
                      value: data.currentBalance,
                    ),
                    ExpendedItemWidget(
                      title: Strings.timeAndDate.tr,
                      value: DateFormat('yyyy-MM-dd').format(data.dateTime),
                    ),
                    _tatumInputField(data, context),
                  ],
                ),
              ),
            ))
      ],
    );
  }

  _tatumInputField(AddMoney data, context) {
    return Visibility(
      visible: data.status == 'Waiting' && data.confirm == true,
      child: Obx(
        () => Column(
          children: [
            verticalSpace(Dimensions.heightSize * 0.5),
            if (data.status == 'Waiting' && data.confirm == true) ...[
              ...controller.inputFields.map((element) {
                return element;
              }),
              Obx(
                () => controller.isTatumConfirmLoading
                    ? const CustomLoadingAPI()
                    : PrimaryButton(
                        title: Strings.proceed,
                        onPressed: () {
                          controller.tatumConfirmProcess(
                            context,
                            data.confirmUrl,
                          );
                        },
                      ),
              ),
              verticalSpace(Dimensions.heightSize),
            ],
          ],
        ),
      ),
    );
  }
}
