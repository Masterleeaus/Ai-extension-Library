import '../../../../backend/utils/custom_loading_api.dart';
import '../../../../controller/categories/virtual_card/strowallet_card/strowallet_details_controller.dart';
import '../../../../utils/basic_screen_imports.dart';
import '../../../../utils/custom_switch_loading_api.dart';
import '../../../../utils/responsive_layout.dart';
import '../../../../widgets/appbar/appbar_widget.dart';
import '../../../../widgets/others/preview/details_row_widget.dart';

class StrowalletCardDetailsScreen extends StatelessWidget {
  StrowalletCardDetailsScreen({super.key});
  final controller = Get.put(StrowalletCardDetailsController());

  get payload => null;
  @override
  Widget build(
    BuildContext context,
  ) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.details),
        body: Obx(
          () => controller.isLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ),
      ),
    );
  }

  _bodyWidget(
    BuildContext context,
  ) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSize * 0.9,
      ),
      child: ListView(
        children: [_cardDetailsWidget(context), _billingAddressWidget(context)],
      ),
    );
  }

  _cardDetailsWidget(BuildContext context) {
    var myCards = controller.cardDetailsModel.data.myCards;

    return Container(
      margin: EdgeInsets.only(top: Dimensions.heightSize * 0.4),
      decoration: BoxDecoration(
        color: Get.isDarkMode
            ? CustomColor.primaryBGDarkColor
            : CustomColor.primaryBGLightColor,
        borderRadius: BorderRadius.circular(Dimensions.radius * 1.5),
      ),
      child: Column(crossAxisAlignment: crossStart, children: [
        Padding(
          padding: EdgeInsets.only(
            top: Dimensions.paddingSize * 0.7,
            bottom: Dimensions.paddingSize * 0.3,
            left: Dimensions.paddingSize * 0.7,
            right: Dimensions.paddingSize * 0.7,
          ),
          child: const TitleHeading3Widget(
            text: Strings.cardInformation,
            textAlign: TextAlign.left,
          ),
        ),
        const Divider(
          thickness: 1,
          color: CustomColor.primaryLightScaffoldBackgroundColor,
        ),
        Padding(
          padding: EdgeInsets.only(
            top: Dimensions.paddingSize * 0.3,
            bottom: Dimensions.paddingSize * 0.6,
            left: Dimensions.paddingSize * 0.7,
            right: Dimensions.paddingSize * 0.7,
          ),
          child: Column(
            // crossAxisAlignment: crossEnd,
            children: [
              DetailsRowWidget(
                variable: Strings.amount,
                value: myCards.amount.toString(),
              ),
              DetailsRowWidget(
                variable: Strings.cardId,
                value: myCards.cardId,
              ),
              DetailsRowWidget(
                variable: Strings.customerId,
                value: myCards.cardUserId,
              ),

              ///>>>>>>>> card plan
              Visibility(
                visible: controller.isShowSensitive.value == false,
                child: DetailsRowWidget(
                  variable: Strings.cardType,
                  value: myCards.cardBrand,
                ),
              ),

              Visibility(
                visible: controller.isShowSensitive.value == true,
                child: DetailsRowWidget(
                  variable: Strings.cardNumber,
                  value: controller.cardPlan.value,
                ),
              ),

              DetailsRowWidget(
                variable: Strings.cvc,
                value: myCards.cvv,
              ),

              DetailsRowWidget(
                variable: Strings.expiration,
                value: myCards.expiry,
              ),
              DetailsRowWidget(
                variable: Strings.city,
                value: myCards.city,
              ),
              DetailsRowWidget(
                variable: Strings.state,
                value: myCards.state,
              ),
              DetailsRowWidget(
                variable: Strings.zipCode,
                value: myCards.zipCode,
              ),
              Row(
                mainAxisAlignment: mainSpaceBet,
                children: [
                  CustomTitleHeadingWidget(
                    text: Strings.freezeCard,
                    style: CustomStyle.darkHeading4TextStyle.copyWith(
                      color: CustomColor.primaryLightTextColor.withOpacity(0.4),
                    ),
                  ),
                  Obx(
                    () => controller.isCardStatusLoading
                        ? const CustomSwitchLoading(
                            color: CustomColor.whiteColor,
                          )
                        : Switch(
                            activeColor: CustomColor.primaryLightTextColor
                                .withOpacity(0.6),
                            inactiveThumbColor: CustomColor
                                .primaryLightTextColor
                                .withOpacity(0.6),
                            value: controller.isSelected.value,
                            onChanged: ((value) {
                              controller.isSelected.value =
                                  !controller.isSelected.value;
                              controller.cardToggle;
                            }),
                          ),
                  )
                ],
              )
            ],
          ),
        ),
      ]),
    );
  }

  _billingAddressWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(top: Dimensions.heightSize * 0.4),
      decoration: BoxDecoration(
        color: Get.isDarkMode
            ? CustomColor.primaryBGDarkColor
            : CustomColor.primaryBGLightColor,
        borderRadius: BorderRadius.circular(Dimensions.radius * 1.5),
      ),
      child: Column(
          mainAxisSize: mainMin,
          crossAxisAlignment: crossStart,
          children: [
            Padding(
              padding: EdgeInsets.only(
                top: Dimensions.paddingSize * 0.7,
                left: Dimensions.paddingSize * 0.7,
                right: Dimensions.paddingSize * 0.7,
              ),
              child: const TitleHeading3Widget(
                text: Strings.billingAddress,
                textAlign: TextAlign.left,
              ),
            ),
            const Divider(
              thickness: 1,
              color: CustomColor.primaryLightScaffoldBackgroundColor,
            ),
            Flexible(
              child: ListView(
                padding: EdgeInsets.zero,
                shrinkWrap: true,
                children: List.generate(
                    controller.cardDetailsModel.data.businessAddress.length,
                    (i) {
                  var data =
                      controller.cardDetailsModel.data.businessAddress[i];
                  return Padding(
                    padding: EdgeInsets.only(
                      top: Dimensions.paddingSize * 0.3,
                      left: Dimensions.paddingSize * 0.7,
                      right: Dimensions.paddingSize * 0.7,
                    ),
                    child: DetailsRowWidget(
                      variable: data.labelName,
                      value: data.value,
                    ),
                  );
                }),
              ),
            ),
            verticalSpace(Dimensions.heightSize * 0.8),
          ]),
    );
  }
}
