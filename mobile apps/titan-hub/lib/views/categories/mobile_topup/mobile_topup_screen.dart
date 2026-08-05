import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/others/limit_widget.dart';

import '../../../backend/utils/custom_loading_api.dart';
import '../../../controller/categories/mobile_topup/mobile_topup_controller.dart';
import '../../../routes/routes.dart';
import '../../../utils/basic_screen_imports.dart';
import '../../../widgets/dropdown/custom_dropdown_menu.dart';
import '../../../widgets/inputs/input_with_text.dart';
import '../../../widgets/inputs/top_up_phone_number_with_contry_code_input.dart';
import '../../../widgets/others/congratulation_widget.dart';
import '../../../widgets/others/limit_information_widget.dart';

class MobileTopUpScreen extends StatelessWidget {
  final controller = Get.put(MobileTopupController());

  MobileTopUpScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.mobileTopUp),
        body: Obx(
          () => controller.isLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.marginSizeHorizontal,
      ),
      physics: const BouncingScrollPhysics(),
      children: [
        _selectedTopUpType(context),
        if (controller.selectTopUpType.value == "AUTOMATIC") ...[
          _automaticInputWidget(context),
        ] else if (controller.selectTopUpType.value == "MANUAL") ...[
          _inputWidget(context),
        ],
        if (controller.selectTopUpType.value == "MANUAL" ||
            controller.selectTopUpType.value == "AUTOMATIC") ...[
          _buttonWidget(context),
        ]
      ],
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical * 2,
      ),
      child: Obx(
        () => controller.isInsertLoading
            ? const CustomLoadingAPI()
            : PrimaryButton(
                title: Strings.topUpNow,
                disable: controller.selectTopUpType.value == "AUTOMATIC" &&
                    controller.isGettingOperator.value == false,
                onPressed: () {
                  if (controller.selectTopUpType.value == "AUTOMATIC" &&
                      controller.isGettingOperator.value == true) {
                    controller.mobileTopUpAutomaticProcess().then((value) {
                      if (context.mounted) {
                        StatusScreen.show(
                          context: context,
                          subTitle: Strings.yourTopUpSuccess.tr,
                          onPressed: () {
                            Get.offAllNamed(Routes.bottomNavBarScreen);
                          },
                        );
                      }
                    });
                  } else if (controller.selectTopUpType.value == "MANUAL") {
                    controller.type.value = controller
                        .getType(controller.billMethodselected.value)!;
                    controller
                        .topUpApiProcess(
                            amount: controller.amountController.text,
                            number: controller.mobileNumberController.text,
                            type: controller.type.value)
                        .then((value) {
                      if (context.mounted) {
                        StatusScreen.show(
                          context: context,
                          subTitle: Strings.yourTopUpSuccess.tr,
                          onPressed: () {
                            Get.offAllNamed(Routes.bottomNavBarScreen);
                          },
                        );
                      }
                    });
                  }
                },
              ),
      ),
    );
  }

  //drop down input,biil number input ,and amount input
  _inputWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(top: Dimensions.marginSizeVertical * 0.5),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          CustomTitleHeadingWidget(
            text: Strings.mobileTopUp,
            style: CustomStyle.darkHeading4TextStyle.copyWith(
              fontWeight: FontWeight.w600,
              color: Get.isDarkMode
                  ? CustomColor.primaryDarkTextColor
                  : CustomColor.primaryTextColor,
            ),
          ),
          verticalSpace(Dimensions.heightSize * 0.5),
          CustomDropdownMenu<String>(
            itemsList: controller.billList,
            selectMethod: controller.billMethodselected,
            onChanged: ((p0) => controller.billMethodselected.value = p0!),
          ),
          verticalSpace(Dimensions.heightSize),
          TopUpPhoneNumberInputWidget(
            isoCode: controller.isoCode,
            countryCode: controller.countryCode,
            controller: controller.mobileNumberController,
            hint: Strings.xxx,
            label: Strings.phoneNumber,
            keyBoardType: TextInputType.number,
            onFieldSubmitted: (value) {
              // controller.getOperator();
            },
          ),
          verticalSpace(Dimensions.heightSize),
          InputWithText(
            controller: controller.amountController,
            hint: Strings.zero00,
            label: Strings.amount,
            suffixText: controller.baseCurrency.value,
            onChanged: (v) {
              controller.getFee(rate: controller.rate.value);
            },
          ),
          Obx(
            () {
              return LimitWidget(
                fee:
                    '${controller.totalFee.value.toStringAsFixed(4)} ${controller.baseCurrency.value}',
                limit:
                    '${controller.limitMin} - ${controller.limitMax} ${controller.baseCurrency.value}',
              );
            },
          ),
          LimitInformationWidget(
            showDailyLimit: controller.dailyLimit.value == 0.0 ? false : true,
            showMonthlyLimit:
                controller.monthlyLimit.value == 0.0 ? false : true,
            transactionLimit:
                '${controller.limitMin.value} - ${controller.limitMax.value} ${controller.baseCurrency.value}',
            dailyLimit:
                '${controller.dailyLimit.value} ${controller.baseCurrency.value}',
            monthlyLimit:
                '${controller.monthlyLimit.value} ${controller.baseCurrency.value}',
            remainingMonthLimit:
                '${controller.remainingController.remainingMonthLyLimit.value} ${controller.baseCurrency.value}',
            remainingDailyLimit:
                '${controller.remainingController.remainingDailyLimit.value} ${controller.baseCurrency.value}',
          ),
        ],
      ),
    );
  }

  _selectedTopUpType(BuildContext context) {
    return Column(
      crossAxisAlignment: crossStart,
      children: [
        CustomTitleHeadingWidget(
          text: Strings.selectTopUpType,
          style: CustomStyle.darkHeading4TextStyle.copyWith(
            fontWeight: FontWeight.w600,
            color: Get.isDarkMode
                ? CustomColor.primaryDarkTextColor
                : CustomColor.primaryTextColor,
          ),
        ),
        verticalSpace(Dimensions.heightSize * 0.5),
        CustomDropdownMenu<String>(
          itemsList: controller.topUpTypeList,
          selectMethod: controller.selectTopUpType,
          onChanged: ((p0) => controller.selectTopUpType.value = p0!),
        ),
      ],
    );
  }

  _automaticInputWidget(BuildContext context) {
    return Column(
      crossAxisAlignment: crossStart,
      children: [
        verticalSpace(Dimensions.heightSize * 0.5),
        TopUpPhoneNumberInputWidget(
          isoCode: controller.isoCode,
          countryCode: controller.countryCode,
          controller: controller.mobileNumberController,
          hint: Strings.xxx,
          label: Strings.phoneNumber,
          keyBoardType: TextInputType.number,
          onTapOutside: (v) {
            controller.getDetectOperator();
          },
          onFieldSubmitted: (v) {
            controller.getDetectOperator();
          },
        ),
        if (controller.isGettingOperator.value) ...[
          verticalSpace(Dimensions.heightSize * 0.5),
          if (controller.detectOperatorModel.data.data!.denominationType !=
              'FIXED')
            Column(
              children: [
                InputWithText(
                  controller: controller.amountController,
                  hint: Strings.zero00,
                  label: Strings.amount,
                  suffixText: controller.operatorCurrency.value,
                  onChanged: (v) {
                    controller.getFee(rate: controller.rate.value);
                  },
                ),
                verticalSpace(Dimensions.heightSize),
              ],
            ),
          const TitleHeading4Widget(
            text: Strings.amount,
            fontWeight: FontWeight.w600,
          ),
          verticalSpace(Dimensions.heightSize * 0.7),

          ///todo
          if (controller.detectOperatorModel.data.data!.denominationType ==
              'FIXED') ...[
            Wrap(
              children: List.generate(
                controller
                    .detectOperatorModel.data.data!.localFixedAmounts!.length,
                (index) {
                  var amount = double.parse(controller
                      .detectOperatorModel.data.data!.localFixedAmounts![index]
                      .toString());
                  return InkWell(
                    highlightColor: Colors.transparent,
                    hoverColor: Colors.transparent,
                    splashColor: Colors.transparent,
                    onTap: () {
                      controller.selectedAmount.value = index;
                      controller.amountController.text = amount.toString();
                      controller.getFee(rate: controller.rate.value);
                    },
                    child: Container(
                      width: MediaQuery.sizeOf(context).width * 0.18,
                      padding: EdgeInsets.all(Dimensions.paddingSize * 0.4),
                      margin: EdgeInsets.only(
                        right: Dimensions.marginSizeHorizontal * 0.4,
                        bottom: Dimensions.marginSizeVertical * 0.4,
                      ),
                      decoration: BoxDecoration(
                        color: controller.selectedAmount.value == index
                            ? CustomColor.primaryLightColor
                            : CustomColor.kDarkBlue,
                        borderRadius: BorderRadius.circular(Dimensions.radius),
                      ),
                      child: Center(
                        child: TitleHeading4Widget(
                          text: amount.toString(),
                          color: controller.selectedAmount.value != index
                              ? CustomColor.primaryLightColor
                              : Colors.white,
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
          Obx(
            () {
              return LimitWidget(
                showLimit: controller
                        .detectOperatorModel.data.data!.denominationType !=
                    'FIXED',
                fee:
                    '${controller.fixedCharge.value.toStringAsFixed(2)} ${controller.baseCurrency.value} + ${controller.fixedCharge.value.toStringAsFixed(2)}%',
                limit:
                    '${controller.operatorLimitMin} - ${controller.operatorLimitMax} ${controller.operatorCurrency.value}',
              );
            },
          ),

          LimitInformationWidget(
            showDailyLimit: controller.dailyLimit.value == 0.0 ? false : true,
            showMonthlyLimit:
                controller.monthlyLimit.value == 0.0 ? false : true,
            transactionLimit:
                '${controller.limitMin.value.toStringAsFixed(2)} - ${controller.limitMax.value} ${controller.operatorCurrency.value}',
            dailyLimit:
                '${controller.dailyLimit.value.toStringAsFixed(2)} ${controller.operatorCurrency.value}',
            monthlyLimit:
                '${controller.monthlyLimit.value} ${controller.operatorCurrency.value}',
            remainingMonthLimit:
                '${controller.remainingController.remainingMonthLyLimit.value} ${controller.operatorCurrency.value}',
            remainingDailyLimit:
                '${controller.remainingController.remainingDailyLimit.value} ${controller.operatorCurrency.value}',
          ),
        ]
      ],
    );
  }
}
