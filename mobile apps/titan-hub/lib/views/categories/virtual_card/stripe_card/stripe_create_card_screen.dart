import '../../../../backend/utils/custom_loading_api.dart';
import '../../../../backend/utils/custom_snackbar.dart';
import '../../../../controller/categories/virtual_card/stripe_card/stripe_card_controller.dart';
import '../../../../custom_assets/assets.gen.dart';
import '../../../../utils/basic_screen_imports.dart';
import '../../../../widgets/inputs/input_with_text.dart';
import '../../../../widgets/others/flip_card_widget.dart';

class StripeCreateCardScreen extends StatelessWidget {
  const StripeCreateCardScreen({super.key, required this.controller});
  final StripeCardController controller;
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Obx(
        () => controller.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.7),
      children: [
        _imageWidget(context),
        _cardLimitShow(),
        _inputFields(context),
        _limitBalance(context),
        _chargeWidget(context),
        _buttonWidget(context),
      ],
    );
  }

  _imageWidget(BuildContext context) {
    return CrateFlipCardWidget(
      title: Strings.visa,
      availableBalance: Strings.cardHolder,
      cardNumber: 'xxxx xxxx xxxx xxxx',
      expiryDate: 'xx/xx',
      balance: 'xx',
      validAt: 'xx',
      cvv: 'xxx',
      logo: Assets.logo.logo.path,
      isNetworkImage: false,
    );
  }

  _inputFields(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(top: Dimensions.paddingSize),
      child: InputWithText(
        controller: controller.fundAmountController,
        hint: Strings.zero00,
        label: Strings.fundAmount,
        suffixText: controller.stripeCardModel.data.baseCurr,
        onChanged: (value) {
          controller.getStripeCardInfo();
        },
      ),
    );
  }

  _limitBalance(BuildContext context) {
    var userData = controller.stripeCardModel.data.userWallet;
    var limitData = controller.stripeCardModel.data.cardCharge;
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 0.3,
        bottom: Dimensions.marginSizeVertical * 2,
      ),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          Row(
            children: [
              TitleHeading4Widget(
                text: Strings.limit,
                color: CustomColor.primaryLightColor,
              ),
              TitleHeading4Widget(
                text:
                    ": ${limitData.minLimit} ${userData.currency} - ${limitData.maxLimit} ${userData.currency}",
                color: CustomColor.primaryLightColor,
              ),
            ],
          ),
          verticalSpace(Dimensions.heightSize * 0.3),
          Row(
            children: [
              TitleHeading4Widget(
                text: Strings.balance,
                color: CustomColor.primaryLightColor,
              ),
              TitleHeading4Widget(
                text: ": ${userData.balance} ${userData.currency}",
                color: CustomColor.primaryLightColor,
              ),
            ],
          ),
        ],
      ),
    );
  }

  _chargeWidget(BuildContext context) {
    var userData = controller.stripeCardModel.data.userWallet;
    return Column(
      mainAxisAlignment: mainCenter,
      children: [
        Row(
          mainAxisAlignment: mainSpaceBet,
          children: [
            const TitleHeading4Widget(text: Strings.totalCharge),
            Obx(
              () => TitleHeading4Widget(
                text: "${controller.totalCharge.value} ${userData.currency}",
                fontSize: Dimensions.headingTextSize5,
              ),
            ),
          ],
        ),
        verticalSpace(Dimensions.heightSize * 0.4),
        Row(
          mainAxisAlignment: mainSpaceBet,
          children: [
            const TitleHeading4Widget(text: Strings.totalPay),
            Obx(
              () => TitleHeading4Widget(
                text: "${controller.totalPay.value} ${userData.currency}",
                fontSize: Dimensions.headingTextSize5,
              ),
            ),
          ],
        ),
      ],
    );
  }

  _buttonWidget(BuildContext context) {
    var data = controller.stripeCardModel.data.cardCharge;
    return Container(
      margin: EdgeInsets.symmetric(vertical: Dimensions.paddingSize * 1.4),
      child: Obx(
        () => controller.isBuyCardLoading
            ? const CustomLoadingAPI()
            : PrimaryButton(
                title: Strings.confirm,
                onPressed: () {
                  double amount =
                      double.parse(controller.fundAmountController.text);
                  if (data.minLimit >= amount && data.maxLimit >= amount) {
                    controller.buyCardProcess(context);
                  } else {
                    CustomSnackBar.error(Strings.pleaseFollowTheLimit);
                  }
                },
                borderColor: CustomColor.primaryLightColor,
                buttonColor: CustomColor.primaryLightColor,
              ),
      ),
    );
  }

  _cardLimitShow() {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingVerticalSize * 0.3),
      child: Row(
        mainAxisAlignment: mainCenter,
        children: [
          const TitleHeading3Widget(text: Strings.myCard),
          horizontalSpace(Dimensions.widthSize * 0.4),
          TitleHeading3Widget(
              text:
                  '${controller.stripeCardModel.data.myCard.length}/${controller.stripeCardModel.data.cardBasicInfo.cardCreateLimit}'),
        ],
      ),
    );
  }
}
